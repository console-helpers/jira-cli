<?php
/**
 * This file is part of the jira-cli library.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @copyright Alexander Obuhovich <aik.bold@gmail.com>
 * @link      https://github.com/aik099/jira-cli
 */

namespace aik099\JiraCLI;


use aik099\JiraCLI\Config\ConfigEditor;
use GuzzleHttp\Client;

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
		);

		$this['jira_rest'] = function ($c) {
			/** @var ConfigEditor $config_editor */
			$config_editor = $c['config_editor'];

			return new JiraRest(
				$config_editor->get('jira.url'),
				$config_editor->get('jira.user'),
				$config_editor->get('jira.password'),
				new Client()
			);
		};
	}

}
