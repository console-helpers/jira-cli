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
use chobie\Jira\Api\Result;
use ConsoleHelpers\ConsoleKit\Config\ConfigEditor;
use ConsoleHelpers\ConsoleKit\Command\AbstractCommand as BaseCommand;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;

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
	 * Jira REST client.
	 *
	 * @var Api
	 */
	protected $jiraApi;

	/**
	 * Prepare dependencies.
	 *
	 * @return void
	 */
	protected function prepareDependencies()
	{
		$container = $this->getContainer();

		$this->_configEditor = $container['config_editor'];
		$this->jiraApi = $container['jira_api'];
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

	/**
	 * Checks if an issue key is valid.
	 *
	 * @param string $issue_key Issue key.
	 *
	 * @return boolean
	 */
	public function isValidIssueKey($issue_key)
	{
		return preg_match('/^([A-Z]+-[0-9]+)$/', $issue_key);
	}

	/**
	 * Return possible values for the named argument
	 *
	 * @param string            $argumentName Argument name.
	 * @param CompletionContext $context      Completion context.
	 *
	 * @return array
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context)
	{
		$ret = parent::completeArgumentValues($argumentName, $context);

		if ( $argumentName === 'project_key' ) {
			return $this->getProjectKeys();
		}

		return $ret;
	}

	/**
	 * Returns possible link names.
	 *
	 * @return array
	 */
	protected function getProjectKeys()
	{
		$ret = array();
		$response = $this->jiraApi->getProjects();

		if ( $response instanceof Result ) {
			$response = $response->getResult();
		}

		foreach ( $response as $project_data ) {
			$ret[] = $project_data['key'];
		}

		return $ret;
	}

}
