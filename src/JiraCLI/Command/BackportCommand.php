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
use ConsoleHelpers\JiraCLI\Issue\BackportableIssueCloner;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BackportCommand extends AbstractCommand
{

	const ISSUE_LINK_NAME = 'Backports';

	const SUMMARY_COLUMN_WIDTH = 70;

	/**
	 * Issue cloner.
	 *
	 * @var BackportableIssueCloner
	 */
	protected $issueCloner;

	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this
			->setName('backport')
			->setDescription('Shows/creates backport issues')
			->addArgument(
				'project_key',
				InputArgument::REQUIRED,
				'Project key, e.g. <comment>JRA</comment>'
			)
			->addOption(
				'create',
				null,
				InputOption::VALUE_NONE,
				'Creates missing backported issues'
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

		$this->issueCloner = $container['backportable_issue_cloner'];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws CommandException When no backportable issues were found.
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$project_key = $this->io->getArgument('project_key');

		if ( !in_array($project_key, $this->jiraApi->getProjectKeys()) ) {
			throw new CommandException('The project with "' . $project_key . '" key does\'t exist.');
		}

		$issues = $this->issueCloner->getIssues(
			'project = ' . $project_key . ' AND labels = backportable',
			self::ISSUE_LINK_NAME,
			BackportableIssueCloner::LINK_DIRECTION_INWARD,
			array($project_key)
		);
		$issue_count = count($issues);

		if ( !$issue_count ) {
			throw new CommandException('No backportable issues found.');
		}

		$this->io->writeln(
			'Found <info>' . $issue_count . '</info> backportable issues in <info>' . $project_key . '</info> project.'
		);

		if ( $this->io->getOption('create') ) {
			$this->createBackportsIssues($project_key, $issues);
		}
		else {
			$this->showBackportableIssues($issues);
		}

		$this->showStatistics();
	}

	/**
	 * Shows backportable issues.
	 *
	 * @param array $issues Backportable Issues.
	 *
	 * @return void
	 */
	protected function showBackportableIssues(array $issues)
	{
		$table = new Table($this->io->getOutput());

		foreach ( $issues as $issue_pair ) {
			/** @var Issue $issue */
			$issue = $issue_pair[0];

			/** @var Issue $backported_by_issue */
			$backported_by_issue = $issue_pair[1];

			$issue_status = $this->issueCloner->getIssueStatusName($issue);
			$row_data = array(
				$issue->getKey(),
				wordwrap($issue->get('summary'), self::SUMMARY_COLUMN_WIDTH),
				$issue_status,
			);

			if ( is_object($backported_by_issue) ) {
				$backported_by_issue_status = $this->issueCloner->getIssueStatusName($backported_by_issue);
				$row_data[] = $backported_by_issue->getKey();
				$row_data[] = wordwrap($backported_by_issue->get('summary'), self::SUMMARY_COLUMN_WIDTH);
				$row_data[] = $backported_by_issue_status;
			}
			else {
				$row_data[] = '';
				$row_data[] = '';
				$row_data[] = '';
			}

			$table->addRow($row_data);
		}

		$table->setHeaders(array(
			'From Key',
			'From Summary',
			'From Status',
			'To Key',
			'To Summary',
			'To Status',
		));

		$table->render();
	}

	/**
	 * Creates backports issues.
	 *
	 * @param string $project_key Project key.
	 * @param array  $issue_pairs Backportable Issues.
	 *
	 * @return void
	 */
	protected function createBackportsIssues($project_key, array $issue_pairs)
	{
		foreach ( $issue_pairs as $issue_pair ) {
			/** @var Issue $issue */
			$issue = $issue_pair[0];

			/** @var Issue $linked_issue */
			$linked_issue = $issue_pair[1];

			$this->io->write('Processing "<info>' . $issue->getKey() . '</info>" issue ... ');

			if ( is_object($linked_issue) ) {
				$this->io->writeln(
					'linked issue "<info>' . $linked_issue->getKey() . '</info>" already exists.'
				);
				continue;
			}

			$components = array();

			foreach ( $issue->get('components') as $component ) {
				$components[] = $component['id'];
			}

			$linked_issue_key = $this->issueCloner->createLinkedIssue(
				$issue,
				$project_key,
				self::ISSUE_LINK_NAME,
				BackportableIssueCloner::LINK_DIRECTION_INWARD,
				$components
			);

			$this->io->writeln('linked issue "<info>' . $linked_issue_key . '</info>" created.');
		}
	}

}
