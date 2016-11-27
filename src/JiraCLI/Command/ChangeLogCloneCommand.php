<?php
/**
 * This file is part of the Jira-CLI library.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @copyright Alexander Obuhovich <aik.bold@gmail.com>
 * @link      https://github.com/console-helpers/jira-cli
 */

namespace ConsoleHelpers\JiraCLI\Command;


use chobie\Jira\Issue;
use ConsoleHelpers\ConsoleKit\Exception\CommandException;
use ConsoleHelpers\JiraCLI\Issue\ChangeLogIssueCloner;
use ConsoleHelpers\JiraCLI\JiraApi;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeLogCloneCommand extends AbstractCommand
{

	/**
	 * Issue cloner.
	 *
	 * @var ChangeLogIssueCloner
	 */
	protected $issueCloner;

	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this
			->setName('changelog-clone')
			->setDescription('Clones issue for changelog into another project')
			->addArgument(
				'issue_key',
				InputArgument::REQUIRED,
				'Issue key, e.g. <comment>JRA-1234</comment>'
			)
			->addArgument(
				'project_keys',
				InputArgument::REQUIRED | InputArgument::IS_ARRAY,
				'Project keys, e.g. <comment>PRJ</comment>'
			)
			->addOption(
				'link-name',
				null,
				InputOption::VALUE_REQUIRED,
				'Link name between issues',
				'Blocks'
			);
	}

	/**
	 * Prepare dependencies.
	 *
	 * @return void
	 */
	protected function prepareDependencies()
	{
		parent::prepareDependencies();

		$container = $this->getContainer();

		$this->issueCloner = $container['changelog_issue_cloner'];
	}

	/**
	 * Return possible values for the named option
	 *
	 * @param string            $optionName Option name.
	 * @param CompletionContext $context    Completion context.
	 *
	 * @return array
	 */
	public function completeOptionValues($optionName, CompletionContext $context)
	{
		$ret = parent::completeOptionValues($optionName, $context);

		if ( $optionName === 'link-name' ) {
			return $this->jiraApi->getIssueLinkTypeNames();
		}

		return $ret;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws CommandException When no backportable issues were found.
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$issue_key = $this->io->getArgument('issue_key');
		$link_name = $this->io->getOption('link-name');

		if ( !in_array($link_name, $this->jiraApi->getIssueLinkTypeNames()) ) {
			throw new CommandException('The "' . $link_name . '" link name doesn\'t exist.');
		}

		$project_keys = $this->io->getArgument('project_keys');
		$non_existing_projects = array_diff($project_keys, $this->jiraApi->getProjectKeys());

		if ( $non_existing_projects ) {
			throw new CommandException(
				'These projects doesn\'t exist: "' . implode('", "', $non_existing_projects) . '".'
			);
		}

		$issues = $this->issueCloner->getIssues(
			'key = ' . $issue_key,
			$link_name,
			ChangeLogIssueCloner::LINK_DIRECTION_OUTWARD,
			$project_keys
		);
		$issue_count = count($issues);

		if ( !$issue_count ) {
			throw new CommandException('The "' . $issue_key . '" issue not found.');
		}

		foreach ( $issues as $issue_pair ) {
			/** @var Issue $issue */
			/** @var Issue $linked_issue */
			list($issue, $linked_issue, $link_project_key) = $issue_pair;

			$issue_project = $issue->get('project');

			if ( $issue_project['key'] === $link_project_key ) {
				throw new CommandException('Creating of linked issue in same project is not supported.');
			}

			if ( is_object($linked_issue) ) {
				$this->io->writeln(sprintf(
					'The "<info>%s</info>" issue already has "<info>%s</info>" link to "<info>%s</info>" issue.',
					$issue->getKey(),
					$link_name,
					$linked_issue->getKey()
				));

				continue;
			}

			$components = array();
			$project_components = $this->jiraApi->getProjectComponentMapping(
				$link_project_key,
				JiraApi::CACHE_DURATION_ONE_MONTH
			);

			if ( $project_components ) {
				$component_name = $this->io->choose(
					'Select linked issue component in "' . $link_project_key . '" project:',
					$project_components,
					'',
					'The component isn\'t valid'
				);

				$components[] = array_search($component_name, $project_components);
			}

			$linked_issue_key = $this->issueCloner->createLinkedIssue(
				$issue,
				$link_project_key,
				$link_name,
				ChangeLogIssueCloner::LINK_DIRECTION_OUTWARD,
				$components
			);

			$this->io->writeln(sprintf(
				'The "<info>%s</info>" issue now has "<info>%s</info>" link to "<info>%s</info>" issue.',
				$issue->getKey(),
				$link_name,
				$linked_issue_key
			));
		}
	}

}
