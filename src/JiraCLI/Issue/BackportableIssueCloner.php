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


use chobie\Jira\Api;
use chobie\Jira\Issue;

class BackportableIssueCloner extends IssueCloner
{

	/**
	 * IssueCloner constructor.
	 *
	 * @param Api $jira_api Jira REST client.
	 */
	public function __construct(Api $jira_api)
	{
		parent::__construct($jira_api);

		$this->queryFields[] = 'status';
		$this->queryFields[] = 'components';
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
		$issue_status = $this->getIssueStatusName($issue);
		$linked_issue_status = $this->getIssueStatusName($linked_issue);

		return $issue_status === 'Resolved' && $linked_issue_status === 'Resolved';
	}

}
