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
use chobie\Jira\Issues\Walker;
use ConsoleHelpers\ConsoleKit\Exception\CommandException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BackportCommand extends AbstractCommand
{

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
	 * {@inheritdoc}
	 *
	 * @throws CommandException When no backportable issues were found.
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->jiraApi->setOptions(0); // Don't expand fields.
		$project_key = $this->io->getArgument('project_key');

		$backportable_issues = $this->getBackportableIssues($project_key);
		$issue_count = count($backportable_issues);

		if ( !$issue_count ) {
			throw new CommandException('No backportable issues found.');
		}

		$this->io->writeln(
			'Found <info>' . $issue_count . '</info> backportable issues in <info>' . $project_key . '</info> project.'
		);

		if ( $this->io->getOption('create') ) {
			$this->createBackportsIssues($backportable_issues);
		}
		else {
			$this->showBackportableIssues($backportable_issues);
		}
	}

	/**
	 * Shows backportable issues.
	 *
	 * @param array $backportable_issues Backportable Issues.
	 *
	 * @return void
	 */
	protected function showBackportableIssues(array $backportable_issues)
	{
		$table = new Table($this->io->getOutput());

		foreach ( $backportable_issues as $issue_pair ) {
			/** @var Issue $issue */
			$issue = $issue_pair[0];

			/** @var Issue $backported_by_issue */
			$backported_by_issue = $issue_pair[1];

			$issue_status = $this->getIssueStatusName($issue);
			$row_data = array(
				$issue->getKey(),
				$issue->get('summary'),
				$issue_status,
			);

			if ( is_object($backported_by_issue) ) {
				$backported_by_issue_status = $this->getIssueStatusName($backported_by_issue);
				$row_data[] = $backported_by_issue->getKey();
				$row_data[] = $backported_by_issue->get('summary');
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
	 * @param array $backportable_issues Backportable Issues.
	 *
	 * @return void
	 */
	protected function createBackportsIssues(array $backportable_issues)
	{
		// TODO: Implement.
	}

	/**
	 * Returns backportable issues.
	 *
	 * @param string $project_key Project key.
	 *
	 * @return array
	 */
	protected function getBackportableIssues($project_key)
	{
		$walker = new Walker($this->jiraApi);
		$walker->push(
			'project = ' . $project_key . ' AND labels = backportable',
			'summary,status,issuelinks'
		);

		$ret = array();

		foreach ( $walker as $issue ) {
			$backported_by_issue = $this->getBackportedBy($issue);

			$issue_status = $this->getIssueStatusName($issue);

			if ( is_object($backported_by_issue) ) {
				$backported_by_issue_status = $this->getIssueStatusName($backported_by_issue);

				// Exclude already processed issues.
				if ( $issue_status === 'Resolved' && $backported_by_issue_status === 'Resolved' ) {
					continue;
				}
			}

			$ret[] = array($issue, $backported_by_issue);
		}

		return $ret;
	}

	/**
	 * Returns issue, which backports given issue.
	 *
	 * @param Issue $issue Issue.
	 *
	 * @return Issue|null
	 */
	protected function getBackportedBy(Issue $issue)
	{
		foreach ( $issue->get('issuelinks') as $issue_link ) {
			if ( $issue_link['type']['name'] !== 'Backports' ) {
				continue;
			}

			if ( array_key_exists('inwardIssue', $issue_link) ) {
				return new Issue($issue_link['inwardIssue']);
			}
		}

		return null;
	}

	/**
	 * Returns issue status name.
	 *
	 * @param Issue $issue Issue.
	 *
	 * @return string
	 */
	protected function getIssueStatusName(Issue $issue)
	{
		$status = $issue->get('status');

		return $status['name'];
	}

}
