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
use ConsoleHelpers\JiraCLI\JiraApi;

class ChangeLogIssueCloner extends IssueCloner
{

	/**
	 * IssueCloner constructor.
	 *
	 * @param JiraApi $jira_api Jira REST client.
	 */
	public function __construct(JiraApi $jira_api)
	{
		parent::__construct($jira_api);

		$this->queryFields[] = 'project';
		$this->queryFields[] = 'type';
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
		$issue_type = $linked_issue->get('issuetype');

		return $issue_type['id'] === $this->getChangelogEntryIssueTypeId();
	}

}
