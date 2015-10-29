<?php
/**
 * This file is part of the jira-cli library.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @copyright Alexander Obuhovich <aik.bold@gmail.com>
 * @link      https://github.com/aik099/jira-cli
 */

namespace aik099\JiraCLI\Command;


use aik099\JiraCLI\Config\ConfigEditor;
use aik099\JiraCLI\JiraRest;
use ConsoleHelpers\ConsoleKit\Command\AbstractCommand as BaseCommand;

/**
 * Base command class.
 */
abstract class AbstractCommand extends BaseCommand
{

	/**
	 * Config editor.
	 *
	 * @var ConfigEditor
	 */
	private $_configEditor;

	/**
	 * Jira REST.
	 *
	 * @var JiraRest
	 */
	protected $jiraRest;

	/**
	 * Prepare dependencies.
	 *
	 * @return void
	 */
	protected function prepareDependencies()
	{
		$container = $this->getContainer();

		$this->_configEditor = $container['config_editor'];
		$this->jiraRest = $container['jira_rest'];
	}

	/**
	 * Returns command setting value.
	 *
	 * @param string $name Name.
	 *
	 * @return mixed
	 */
	protected function getSetting($name)
	{
		return $this->_configEditor->get($name);
	}

}