<?php
/**
 * This file is part of the Jira-CLI library.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @copyright Alexander Obuhovich <aik.bold@gmail.com>
 * @link      https://github.com/console-helpers/jira-cli
 */

namespace ConsoleHelpers\JiraCLI;


use chobie\Jira\Api\Authentication\Basic;
use ConsoleHelpers\ConsoleKit\Config\ConfigEditor;
use ConsoleHelpers\JiraCLI\Cache\CacheFactory;
use ConsoleHelpers\JiraCLI\Issue\BackportableIssueCloner;
use ConsoleHelpers\JiraCLI\Issue\ChangeLogIssueCloner;

class Container extends \ConsoleHelpers\ConsoleKit\Container
{

	/**
	 * {@inheritdoc}
	 */
	public function __construct(array $values = array())
	{
		parent::__construct($values);

		$this['app_name'] = 'Jira-CLI';
		$this['app_version'] = '@git-version@';

		$this['working_directory_sub_folder'] = '.jira-cli';

		$this['config_defaults'] = array(
			'jira.url' => '',
			'jira.username' => '',
			'jira.password' => '',
			'cache.provider' => '',
		);

		$this['cache'] = function ($c) {
			/** @var ConfigEditor $config_editor */
			$config_editor = $c['config_editor'];
			$cache_provider = $config_editor->get('cache.provider');

			$cache_factory = new CacheFactory('jira_url:' . $config_editor->get('jira.url'));

			return $cache_factory->create('chain', array('array', $cache_provider));
		};

		$this['jira_api'] = function ($c) {
			/** @var ConfigEditor $config_editor */
			$config_editor = $c['config_editor'];

			$authentication = new Basic(
				$config_editor->get('jira.username'),
				$config_editor->get('jira.password')
			);

			$api = new JiraApi($config_editor->get('jira.url'), $authentication);
			$api->setCache($c['cache']);

			return $api;
		};

		$this['backportable_issue_cloner'] = function ($c) {
			return new BackportableIssueCloner($c['jira_api']);
		};

		$this['changelog_issue_cloner'] = function ($c) {
			return new ChangeLogIssueCloner($c['jira_api']);
		};
	}

}
