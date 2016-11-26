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


use chobie\Jira\Api;
use chobie\Jira\Issue;
use ConsoleHelpers\ConsoleKit\Exception\CommandException;
use ConsoleHelpers\JiraCLI\Issue\ChangeLogIssueCloner;
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
				'project_key',
				InputArgument::REQUIRED,
				'Project key, e.g. <comment>PRJ</comment>'
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
			return $this->getLinkNames();
		}

		return $ret;
	}

	/**
	 * Returns possible link names.
	 *
	 * @return array
	 */
	protected function getLinkNames()
	{
		$cache_key = 'issue_link_types';
		$cached_value = $this->cache->fetch($cache_key);

		if ( $cached_value === false ) {
			$cached_value = array();
			$response = $this->jiraApi->api(Api::REQUEST_GET, '/rest/api/2/issueLinkType', array(), true);

			foreach ( $response['issueLinkTypes'] as $link_type_data ) {
				$cached_value[] = $link_type_data['name'];
			}

			$this->cache->save($cache_key, $cached_value);
		}

		return $cached_value;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws CommandException When no backportable issues were found.
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$project_key = $this->io->getArgument('project_key');

		if ( !in_array($project_key, $this->getProjectKeys()) ) {
			throw new CommandException('The project with "' . $project_key . '" key does\'t exist.');
		}

		$link_name = $this->io->getOption('link-name');

		if ( !in_array($link_name, $this->getLinkNames()) ) {
			throw new CommandException('The "' . $link_name . '" link name doesn\'t exist.');
		}

		$issue_key = $this->io->getArgument('issue_key');
		$issues = $this->issueCloner->getIssues(
			'key = ' . $issue_key,
			$link_name,
			ChangeLogIssueCloner::LINK_DIRECTION_OUTWARD,
			$project_key
		);
		$issue_count = count($issues);

		if ( $issue_count !== 1 ) {
			throw new CommandException('The "' . $issue_key . '" not found.');
		}

		/** @var Issue[] $issue_pair */
		$issue_pair = reset($issues);
		$issue = $issue_pair[0];
		$linked_issue = $issue_pair[1];

		$issue_project = $issue->get('project');

		if ( $issue_project['key'] === $project_key ) {
			throw new CommandException('Creating of linked issue in same project is not supported.');
		}

		if ( is_object($linked_issue) ) {
			$this->io->writeln(sprintf(
				'The "<info>%s</info>" issue already has "<info>%s</info>" link to "<info>%s</info>" issue.',
				$issue->getKey(),
				$link_name,
				$linked_issue->getKey()
			));

			return;
		}

		$components = array();
		$project_components = $this->getProjectComponents($project_key);

		if ( $project_components ) {
			$component_name = $this->io->choose(
				'Select linked issue component:',
				$project_components,
				'',
				'The component isn\'t valid'
			);

			$components[] = array_search($component_name, $project_components);
		}

		$linked_issue_key = $this->issueCloner->createLinkedIssue(
			$issue,
			$project_key,
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

	/**
	 * Returns project components.
	 *
	 * @param string $project_key Project key.
	 *
	 * @return array
	 */
	protected function getProjectComponents($project_key)
	{
		$cache_key = 'project_components[' . $project_key . ']';
		$cached_value = $this->cache->fetch($cache_key);

		if ( $cached_value === false ) {
			$cached_value = array();
			$project_components = $this->jiraApi->getProjectComponents($project_key);

			foreach ( $project_components as $project_component_data ) {
				$cached_value[$project_component_data['id']] = $project_component_data['name'];
			}

			$this->cache->save($cache_key, $cached_value, 2592000); // Cache for 1 month.
		}

		return $cached_value;
	}

}
