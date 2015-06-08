<?php
/**
 * This file is part of the jira-cli library.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @copyright Alexander Obuhovich <aik.bold@gmail.com>
 * @link      https://github.com/aik099/jira-cli
 */

namespace aik099\JiraCli;


use aik099\JiraCli\Command\DownloadAttachment;
use Pimple\Container;
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
	 * Configuration.
	 *
	 * @var Config
	 */
	protected $config;

	/**
	 * Constructor.
	 *
	 * @param string $name    The name of the application.
	 * @param string $version The version of the application.
	 */
	public function __construct($name = 'Jira Cli', $version = '@package_version@')
	{
		if ( base64_decode('QHBhY2thZ2VfdmVyc2lvbkA=') !== $version ) {
			$version = ltrim($version, 'v');
		}

		parent::__construct($name, $version);
	}

	/**
	 * Initializes all the composer commands.
	 *
	 * @return Command[] An array of default Command instances.
	 */
	protected function getDefaultCommands()
	{
		$commands = parent::getDefaultCommands();
		$commands[] = new DownloadAttachment();

		return $commands;
	}

	/**
	 * Initializes application.
	 *
	 * @param Container $dic Dependency injection container.
	 *
	 * @return self
	 */
	public function init(Container $dic)
	{
		$this->dic = $dic;
		$this->config = $this->dic['config'];

		return $this;
	}

	/**
	 * Returns object from DI container.
	 *
	 * @param string $name Object name.
	 *
	 * @return \stdClass
	 */
	public function getObject($name)
	{
		return $this->dic[$name];
	}

	/**
	 * Returns setting value by name.
	 *
	 * @param string $name Setting name.
	 *
	 * @return string
	 */
	public function getSetting($name)
	{
		return $this->config->get($name);
	}

}
