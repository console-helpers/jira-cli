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


use aik099\JiraCLI\Command\DownloadAttachment;
use Pimple\Container;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;

class Application extends BaseApplication
{

	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	protected $dic;

	/**
	 * Creates application.
	 *
	 * @param Container $container Container.
	 */
	public function __construct(Container $container)
	{
		$this->dic = $container;

		parent::__construct('Jira-CLI', '@git-version@');

		$helper_set = $this->getHelperSet();
		$helper_set->set($this->dic['container_helper']);

		$container['helper_set'] = function () use ($helper_set) {
			return $helper_set;
		};
	}

	/**
	 * Initializes all the composer commands.
	 *
	 * @return Command[] An array of default Command instances.
	 */
	protected function getDefaultCommands()
	{
		$default_commands = parent::getDefaultCommands();
		$default_commands[] = new DownloadAttachment();
		$default_commands[] = new CompletionCommand();

		return $default_commands;
	}

}
