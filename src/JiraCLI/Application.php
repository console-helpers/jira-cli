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


use ConsoleHelpers\JiraCLI\Command\BackportCommand;
use ConsoleHelpers\JiraCLI\Command\ChangeLogCloneCommand;
use ConsoleHelpers\JiraCLI\Command\DownloadAttachmentCommand;
use ConsoleHelpers\ConsoleKit\Application as BaseApplication;
use ConsoleHelpers\JiraCLI\Command\VersionsCommand;
use Symfony\Component\Console\Command\Command;

class Application extends BaseApplication
{

	/**
	 * Initializes all the composer commands.
	 *
	 * @return Command[] An array of default Command instances.
	 */
	protected function getDefaultCommands()
	{
		$default_commands = parent::getDefaultCommands();
		$default_commands[] = new DownloadAttachmentCommand();
		$default_commands[] = new VersionsCommand();
		$default_commands[] = new BackportCommand();
		$default_commands[] = new ChangeLogCloneCommand();

		return $default_commands;
	}

}
