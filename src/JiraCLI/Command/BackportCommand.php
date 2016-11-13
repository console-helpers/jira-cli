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
use chobie\Jira\Issues\Walker;
use ConsoleHelpers\ConsoleKit\Exception\CommandException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BackportCommand extends AbstractCommand
{

	const ISSUE_LINK_NAME = 'Backports';

	/**
	 * Specifies custom fields to copy during backporting.
	 *
	 * @var array
	 */
	protected $copyCustomFields = array(
		'Change Log Group', 'Change Log Message',
	);

	/**
	 * Custom fields map.
	 *
	 * @var array
	 */
	protected $customFieldsMap = array();

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
		$this->buildCustomFieldsMap();

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
			$this->createBackportsIssues($project_key, $backportable_issues);
		}
		else {
			$this->showBackportableIssues($backportable_issues);
		}
	}

	/**
	 * Builds custom field map.
	 *
	 * @return void
	 */
	protected function buildCustomFieldsMap()
	{
		foreach ( $this->jiraApi->getFields() as $field_key => $field_data ) {
			if ( substr($field_key, 0, 12) === 'customfield_' ) {
				$this->customFieldsMap[$field_data['name']] = $field_key;
			}
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
	 * @param string $project_key         Project key.
	 * @param array  $backportable_issues Backportable Issues.
	 *
	 * @return void
	 * @throws \LogicException When "Changelog Entry" issue type isn't found.
	 * @throws CommandException When failed to create an issue.
	 */
	protected function createBackportsIssues($project_key, array $backportable_issues)
	{
		$issue_type_id = $this->getChangelogEntryIssueTypeId();

		if ( !is_numeric($issue_type_id) ) {
			throw new \LogicException('The "Changelog Entry" issue type not found.');
		}

		foreach ( $backportable_issues as $issue_pair ) {
			/** @var Issue $issue */
			$issue = $issue_pair[0];

			/** @var Issue $backported_by_issue */
			$backported_by_issue = $issue_pair[1];

			$this->io->write('Processing "' . $issue->getKey() . '" issue ... ');

			if ( is_object($backported_by_issue) ) {
				$this->io->writeln('skipping [already has linked issue].');
				continue;
			}

			$create_fields = array(
				'description' => 'See ' . $issue->getKey() . '.',
				'components' => array(),
			);

			foreach ( $this->copyCustomFields as $custom_field ) {
				if ( isset($this->customFieldsMap[$custom_field]) ) {
					$custom_field_id = $this->customFieldsMap[$custom_field];
					$create_fields[$custom_field_id] = $this->getIssueCustomField($issue, $custom_field_id);
				}
			}

			foreach ( $issue->get('components') as $component ) {
				$create_fields['components'][] = array('id' => $component['id']);
			}

			$create_issue_result = $this->jiraApi->createIssue(
				$project_key,
				$issue->get('summary'),
				$issue_type_id,
				$create_fields
			);

			$raw_create_issue_result = $create_issue_result->getResult();

			if ( array_key_exists('errors', $raw_create_issue_result) ) {
				throw new CommandException(sprintf(
					'Failed to create backported issue for "%s" issue. Errors: ' . PHP_EOL . '%s',
					$issue->getKey(),
					print_r($raw_create_issue_result['errors'], true)
				));
			}

			$issue_link_result = $this->jiraApi->api(
				Api::REQUEST_POST,
				'/rest/api/2/issueLink',
				array(
					'type' => array('name' => self::ISSUE_LINK_NAME),
					'inwardIssue' => array('key' => $raw_create_issue_result['key']),
					'outwardIssue' => array('key' => $issue->getKey()),
				)
			);

			$this->io->writeln('linked issue created.');
		}
	}

	/**
	 * Returns ID of "Changelog Entry" issue type.
	 *
	 * @return integer|null
	 */
	protected function getChangelogEntryIssueTypeId()
	{
		foreach ( $this->jiraApi->getIssueTypes() as $issue_type ) {
			if ( $issue_type->getName() === 'Changelog Entry' ) {
				return $issue_type->getId();
			}
		}

		return null;
	}

	/**
	 * Returns custom field value.
	 *
	 * @param Issue  $issue           Issue.
	 * @param string $custom_field_id Custom field ID.
	 *
	 * @return mixed
	 */
	protected function getIssueCustomField(Issue $issue, $custom_field_id)
	{
		$custom_field_data = $issue->get($custom_field_id);

		if ( is_array($custom_field_data) ) {
			return array('value' => $custom_field_data['value']);
		}

		return $custom_field_data;
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
		$needed_fields = array('summary', 'status', 'components', 'issuelinks');

		foreach ( $this->copyCustomFields as $custom_field ) {
			if ( isset($this->customFieldsMap[$custom_field]) ) {
				$needed_fields[] = $this->customFieldsMap[$custom_field];
			}
		}

		$walker = new Walker($this->jiraApi);
		$walker->push(
			'project = ' . $project_key . ' AND labels = backportable',
			implode(',', $needed_fields)
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
			if ( $issue_link['type']['name'] !== self::ISSUE_LINK_NAME ) {
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
