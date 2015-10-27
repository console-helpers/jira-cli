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
use aik099\JiraCLI\Helper\ContainerHelper;
use GuzzleHttp\Client;
use Pimple\Container;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class DIContainer extends Container
{

	/**
	 * {@inheritdoc}
	 */
	public function __construct(array $values = array())
	{
		parent::__construct($values);

		$this['configFile'] = '{base}/config.json';

		$this['working_directory'] = function () {
			$working_directory = new WorkingDirectory();

			return $working_directory->get();
		};

		$this['config_editor'] = function ($c) {
			return new ConfigEditor(str_replace('{base}', $c['working_directory'], $c['configFile']));
		};

		$this['input'] = function () {
			return new ArgvInput();
		};

		$this['output'] = function () {
			return new ConsoleOutput();
		};

		$this['io'] = function ($c) {
			return new ConsoleIO($c['input'], $c['output'], $c['helper_set']);
		};

		// Would be replaced with actual HelperSet from extended Application class.
		$this['helper_set'] = function () {
			return new HelperSet();
		};

		$this['container_helper'] = function ($c) {
			return new ContainerHelper($c);
		};

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
