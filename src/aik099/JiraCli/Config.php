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


class Config
{

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	protected static $defaultSettings = array(
		'jira-url' => '',
		'jira-user' => '',
		'jira-password' => '',
	);

	/**
	 * Settings.
	 *
	 * @var array
	 */
	protected $settings = array();

	public static function createFromFile($filename)
	{
		$home = static::getUserHomeDirectory() . '/.jira_cli';
		$filename = str_replace('{home}', $home, $filename);

		if ( !file_exists($home) ) {
			static::initHomeFolder($filename);
		}

		$settings = json_decode(file_get_contents($filename), true);
		$settings['home'] = $home;

		return new static($settings);
	}

	/**
	 * Returns path to user's home directory.
	 *
	 * @return string
	 * @throws \RuntimeException When user's home directory can't be found.
	 */
	protected static function getUserHomeDirectory()
	{
		if ( defined('PHP_WINDOWS_VERSION_MAJOR') ) {
			if ( !getenv('APPDATA') ) {
				throw new \RuntimeException('The APPDATA environment variable must be set to run correctly');
			}

			return strtr(getenv('APPDATA'), '\\', '/');
		}

		if ( !getenv('HOME') ) {
			throw new \RuntimeException('The HOME environment variable must be set to run correctly');
		}

		return rtrim(getenv('HOME'), '/');
	}

	/**
	 * Creates home folder and default configuration file in it.
	 *
	 * @param string $filename Filename.
	 *
	 * @return void
	 */
	protected static function initHomeFolder($filename)
	{
		mkdir(dirname($filename), 0777, true);
		file_put_contents($filename, json_encode(static::$defaultSettings, JSON_PRETTY_PRINT));
	}

	/**
	 * Creates config instance.
	 *
	 * @param array $settings Settings.
	 */
	public function __construct(array $settings)
	{
		$this->settings = array_merge(static::$defaultSettings, $settings);
	}

	/**
	 * Returns config value.
	 *
	 * @param string $name Config setting name.
	 *
	 * @return mixed
	 */
	public function get($name)
	{
		return isset($this->settings[$name]) ? $this->settings[$name] : false;
	}

}
