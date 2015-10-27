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
use aik099\JiraCLI\ConsoleIO;
use aik099\JiraCLI\Helper\ContainerHelper;
use aik099\JiraCLI\JiraRest;
use Pimple\Container;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base command class.
 */
abstract class AbstractCommand extends Command implements CompletionAwareInterface
{

	/**
	 * Config editor.
	 *
	 * @var ConfigEditor
	 */
	private $_configEditor;

	/**
	 * Console IO.
	 *
	 * @var ConsoleIO
	 */
	protected $io;

	/**
	 * Jira REST.
	 *
	 * @var JiraRest
	 */
	protected $jiraRest;

	/**
	 * {@inheritdoc}
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);

		// Don't use IO from container, because it contains outer IO which doesn't reflect sub-command calls.
		$this->io = new ConsoleIO($input, $output, $this->getHelperSet());

		$this->prepareDependencies();
	}

	/**
	 * Return possible values for the named option
	 *
	 * @param string            $optionName Option name.
	 * @param CompletionContext $context    Completion context.
	 *
	 * @return array
	 */
	public function completeOptionValues($optionName, CompletionContext $context)
	{
		$this->prepareDependencies();

		return array();
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
		$this->prepareDependencies();

		return array();
	}

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

	/**
	 * Returns container.
	 *
	 * @return Container
	 */
	protected function getContainer()
	{
		static $container;

		if ( !isset($container) ) {
			/** @var ContainerHelper $container_helper */
			$container_helper = $this->getHelper('container');

			$container = $container_helper->getContainer();
		}

		return $container;
	}

}
