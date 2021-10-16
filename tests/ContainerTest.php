<?php
/**
 * This file is part of the Jira-CLI library.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @copyright Alexander Obuhovich <aik.bold@gmail.com>
 * @link      https://github.com/console-helpers/jira-cli
 */

namespace Tests\ConsoleHelpers\JiraCLI;


use ConsoleHelpers\JiraCLI\Container;
use Tests\ConsoleHelpers\ConsoleKit\ContainerTest as BaseContainerTest;

class ContainerTest extends BaseContainerTest
{

	public function instanceDataProvider()
	{
		$instance_data = parent::instanceDataProvider();

		$new_instance_data = array(
			'app_name' => array('Jira-CLI', 'app_name'),
			'app_version' => array('@git-version@', 'app_version'),
			'config_defaults' => array(
				array(
					'jira.url' => '',
					'jira.username' => '',
					'jira.password' => '',
					'cache.provider' => '',
				),
				'config_defaults',
			),
			'working_directory_sub_folder' => array('.jira-cli', 'working_directory_sub_folder'),
			'cache' => array('Doctrine\\Common\\Cache\\CacheProvider', 'cache'),
			'jira_api' => array('chobie\\Jira\\Api', 'jira_api'),
			'backportable_issue_cloner' => array('ConsoleHelpers\\JiraCLI\\Issue\\BackportableIssueCloner', 'backportable_issue_cloner'),
			'changelog_issue_cloner' => array('ConsoleHelpers\\JiraCLI\\Issue\\ChangeLogIssueCloner', 'changelog_issue_cloner'),
		);

		return array_merge($instance_data, $new_instance_data);
	}

	/**
	 * Creates container instance.
	 *
	 * @return Container
	 */
	protected function createContainer()
	{
		return new Container();
	}

}
