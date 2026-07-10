<?php
/*
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
use ConsoleHelpers\ConsoleKit\Exception\ApplicationException;
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

		$config_file_name = getenv('CONFIG_FILE');

		if ( $config_file_name !== false ) {
			$this['config_file'] = '{base}/' . $config_file_name;
		}

		$this['config_editor'] = function ($c) use ($config_file_name) {
			$working_directory = $c['working_directory'];
			$config_file = str_replace('{base}', $working_directory, $c['config_file']);

			if ( $config_file_name && !file_exists($config_file) ) {
				$available_config_files = glob($working_directory . '/*.json') ?: array();
				$available_config_files = array_map('basename', $available_config_files);

				if ( $available_config_files ) {
					throw new ApplicationException(sprintf(
						'The "%s" config file doesn\'t exist. Other config files: %s.',
						$config_file,
						implode(', ', $available_config_files)
					));
				}

				throw new ApplicationException(sprintf(
					'The "%s" config file doesn\'t exist.',
					$config_file
				));
			}

			return new ConfigEditor($config_file, $c['config_defaults']);
		};

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
