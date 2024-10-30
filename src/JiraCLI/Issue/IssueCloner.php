<?php
/**
 * This file is part of the Jira-CLI library.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @copyright Alexander Obuhovich <aik.bold@gmail.com>
 * @link      https://github.com/console-helpers/jira-cli
 */

namespace ConsoleHelpers\JiraCLI\Issue;


use chobie\Jira\Issue;
use chobie\Jira\Issues\Walker;
use ConsoleHelpers\JiraCLI\JiraApi;

class IssueCloner
{

	const LINK_DIRECTION_INWARD = 1;

	const LINK_DIRECTION_OUTWARD = 2;

	/**
	 * Jira REST client.
	 *
	 * @var JiraApi
	 */
	protected $jiraApi;

	/**
	 * Specifies custom fields to copy during backporting.
	 *
	 * @var array
	 */
	private $_copyCustomFields = array(
		'Change Log Group', 'Change Log Message',
	);

	/**
	 * Custom fields map.
	 *
	 * @var array
	 */
	private $_customFieldsMap = array();

	/**
	 * Fields to query during issue search.
	 *
	 * @var array
	 */
	protected $queryFields = array('summary', 'issuelinks');

	/**
	 * Project of an issue.
	 *
	 * @var array
	 */
	private $_issueProjects = array();

	/**
	 * IssueCloner constructor.
	 *
	 * @param JiraApi $jira_api Jira REST client.
	 */
	public function __construct(JiraApi $jira_api)
	{
		$this->jiraApi = $jira_api;

		$this->jiraApi->setOptions(0); // Don't expand fields.
	}

	/**
	 * Returns issues.
	 *
	 * @param string  $jql               JQL.
	 * @param string  $link_name         Link name.
	 * @param integer $link_direction    Link direction.
	 * @param array   $link_project_keys Link project keys.
	 *
	 * @return array
	 */
	public function getIssues($jql, $link_name, $link_direction, array $link_project_keys)
	{
		$this->_buildCustomFieldsMap();

		$walker = new Walker($this->jiraApi);
		$walker->push($jql, implode(',', $this->_getQueryFields()));

		$cache = array();

		foreach ( $walker as $issue ) {
			foreach ( $link_project_keys as $link_project_key ) {
				$linked_issue = $this->_getLinkedIssue($issue, $link_name, $link_direction);

				if ( $linked_issue !== null ) {
					$this->_addToProjectDetectionQueue($linked_issue);
				}

				$cache[] = array($issue, $linked_issue, $link_project_key);
			}
		}

		$this->_processProjectDetectionQueue();

		$ret = array();

		foreach ( $cache as $cached_data ) {
			list($issue, $linked_issue, $link_project_key) = $cached_data;

			if ( $linked_issue === null ) {
				$ret[] = $cached_data;
				continue;
			}

			if ( $this->isLinkAccepted($issue, $linked_issue)
				&& $this->_getIssueProject($linked_issue) === $link_project_key
				&& !$this->isAlreadyProcessed($issue, $linked_issue)
			) {
				$ret[] = $cached_data;
			}
		}

		return $ret;
	}

	/**
	 * Adds an issue to the project detection queue.
	 *
	 * @param Issue $issue Issue.
	 *
	 * @return void
	 */
	private function _addToProjectDetectionQueue(Issue $issue)
	{
		$this->_issueProjects[$issue->getKey()] = null;
	}

	/**
	 * Detects projects for queued issues.
	 *
	 * @return void
	 */
	private function _processProjectDetectionQueue()
	{
		$issues_without_projects = array_keys(array_filter($this->_issueProjects, function ($project_key) {
			return $project_key === null;
		}));

		if ( !$issues_without_projects ) {
			return;
		}

		$walker = new Walker($this->jiraApi);
		$walker->push(
			'key IN (' . implode(',', $issues_without_projects) . ')',
			'project'
		);

		foreach ( $walker as $linked_issue ) {
			$linked_issue_project_data = $linked_issue->get('project');
			$this->_issueProjects[$linked_issue->getKey()] = $linked_issue_project_data['key'];
		}
	}

	/**
	 * Returns an issue project.
	 *
	 * @param Issue $issue Issue.
	 *
	 * @return string
	 * @throws \RuntimeException When issue's project isn't available.
	 */
	private function _getIssueProject(Issue $issue)
	{
		$issue_key = $issue->getKey();

		if ( array_key_exists($issue_key, $this->_issueProjects) ) {
			return $this->_issueProjects[$issue_key];
		}

		throw new \RuntimeException('The issue "' . $issue_key . '" project wasn\'t queried.');
	}

	/**
	 * Builds custom field map.
	 *
	 * @return void
	 */
	private function _buildCustomFieldsMap()
	{
		foreach ( $this->jiraApi->getFields() as $field_key => $field_data ) {
			if ( substr($field_key, 0, 12) === 'customfield_' ) {
				$this->_customFieldsMap[$field_data['name']] = $field_key;
			}
		}
	}

	/**
	 * Returns query fields.
	 *
	 * @return array
	 */
	private function _getQueryFields()
	{
		$ret = $this->queryFields;

		foreach ( $this->_copyCustomFields as $custom_field ) {
			if ( isset($this->_customFieldsMap[$custom_field]) ) {
				$ret[] = $this->_customFieldsMap[$custom_field];
			}
		}

		return $ret;
	}

	/**
	 * Returns issue, which backports given issue (project not matched yet).
	 *
	 * @param Issue   $issue          Issue.
	 * @param string  $link_name      Link name.
	 * @param integer $link_direction Link direction.
	 *
	 * @return Issue|null
	 * @throws \InvalidArgumentException When link direction isn't valid.
	 */
	private function _getLinkedIssue(Issue $issue, $link_name, $link_direction)
	{
		foreach ( $issue->get('issuelinks') as $issue_link ) {
			if ( $issue_link['type']['name'] !== $link_name ) {
				continue;
			}

			if ( $link_direction === self::LINK_DIRECTION_INWARD ) {
				$check_key = 'inwardIssue';
			}
			elseif ( $link_direction === self::LINK_DIRECTION_OUTWARD ) {
				$check_key = 'outwardIssue';
			}
			else {
				throw new \InvalidArgumentException('The "' . $link_direction . '" link direction isn\'t valid.');
			}

			if ( array_key_exists($check_key, $issue_link) ) {
				return new Issue($issue_link[$check_key]);
			}
		}

		return null;
	}

	/**
	 * Creates backports issues.
	 *
	 * @param Issue   $issue          Issue.
	 * @param string  $project_key    Project key.
	 * @param string  $link_name      Link name.
	 * @param integer $link_direction Link direction.
	 * @param array   $component_ids  Component IDs.
	 *
	 * @return string
	 * @throws \RuntimeException When failed to create an issue.
	 * @throws \InvalidArgumentException When link direction isn't valid.
	 */
	public function createLinkedIssue(Issue $issue, $project_key, $link_name, $link_direction, array $component_ids)
	{
		$create_fields = array(
			'description' => 'See ' . $issue->getKey() . '.',
			'components' => array(),
		);

		foreach ( $this->_copyCustomFields as $custom_field ) {
			if ( isset($this->_customFieldsMap[$custom_field]) ) {
				$custom_field_id = $this->_customFieldsMap[$custom_field];
				$create_fields[$custom_field_id] = $this->getIssueCustomField($issue, $custom_field_id);
			}
		}

		foreach ( $component_ids as $component_id ) {
			$create_fields['components'][] = array('id' => (string)$component_id);
		}

		$create_issue_result = $this->jiraApi->createIssue(
			$project_key,
			$issue->get('summary'),
			$this->getChangelogEntryIssueTypeId(),
			$create_fields
		);

		$raw_create_issue_result = $create_issue_result->getResult();

		if ( array_key_exists('errors', $raw_create_issue_result) ) {
			throw new \RuntimeException(sprintf(
				'Failed to create linked issue for "%s" issue. Errors: ' . PHP_EOL . '%s',
				$issue->getKey(),
				print_r($raw_create_issue_result['errors'], true)
			));
		}

		if ( $link_direction === self::LINK_DIRECTION_INWARD ) {
			$issue_link_result = $this->jiraApi->api(
				JiraApi::REQUEST_POST,
				'/rest/api/2/issueLink',
				array(
					'type' => array('name' => $link_name),
					'inwardIssue' => array('key' => $raw_create_issue_result['key']),
					'outwardIssue' => array('key' => $issue->getKey()),
				)
			);
		}
		elseif ( $link_direction === self::LINK_DIRECTION_OUTWARD ) {
			$issue_link_result = $this->jiraApi->api(
				JiraApi::REQUEST_POST,
				'/rest/api/2/issueLink',
				array(
					'type' => array('name' => $link_name),
					'inwardIssue' => array('key' => $issue->getKey()),
					'outwardIssue' => array('key' => $raw_create_issue_result['key']),
				)
			);
		}
		else {
			throw new \InvalidArgumentException('The "' . $link_direction . '" link direction isn\'t valid.');
		}

		return $raw_create_issue_result['key'];
	}

	/**
	 * Determines if link was already processed.
	 *
	 * @param Issue $issue        Issue.
	 * @param Issue $linked_issue Linked issue.
	 *
	 * @return boolean
	 */
	protected function isAlreadyProcessed(Issue $issue, Issue $linked_issue)
	{
		return false;
	}

	/**
	 * Determines if link is accepted.
	 *
	 * @param Issue $issue        Issue.
	 * @param Issue $linked_issue Linked issue.
	 *
	 * @return boolean
	 */
	protected function isLinkAccepted(Issue $issue, Issue $linked_issue)
	{
		return true;
	}

	/**
	 * Returns ID of "Changelog Entry" issue type.
	 *
	 * @return integer
	 * @throws \LogicException When "Changelog Entry" issue type wasn't found.
	 */
	protected function getChangelogEntryIssueTypeId()
	{
		static $issue_type_id;

		if ( !isset($issue_type_id) ) {
			foreach ( $this->jiraApi->getIssueTypes() as $issue_type ) {
				if ( $issue_type->getName() === 'Changelog Entry' ) {
					$issue_type_id = $issue_type->getId();
					break;
				}
			}

			if ( !isset($issue_type_id) ) {
				throw new \LogicException('The "Changelog Entry" issue type not found.');
			}
		}

		return $issue_type_id;
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
	 * Returns issue status name.
	 *
	 * @param Issue $issue Issue.
	 *
	 * @return string
	 */
	public function getIssueStatusName(Issue $issue)
	{
		$status = $issue->get('status');

		return $status['name'];
	}

}
