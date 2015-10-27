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


class WorkingDirectory
{

	/**
	 * Creates (if missing) working directory and returns full path to it.
	 *
	 * @return string
	 */
	public function get()
	{
		$working_directory = $this->getUserHomeDirectory() . '/.jira-cli';

		if ( !file_exists($working_directory) ) {
			mkdir($working_directory, 0777, true);
		}

		return $working_directory;
	}

	/**
	 * Returns path to user's home directory.
	 *
	 * @return string
	 * @throws \RuntimeException When user's home directory can't be found.
	 */
	protected function getUserHomeDirectory()
	{
		if ( defined('PHP_WINDOWS_VERSION_MAJOR') ) {
			if ( !getenv('APPDATA') ) {
				throw new \RuntimeException('The APPDATA environment variable must be set to run correctly.');
			}

			return strtr(getenv('APPDATA'), '\\', '/');
		}

		if ( !getenv('HOME') ) {
			throw new \RuntimeException('The HOME environment variable must be set to run correctly.');
		}

		return rtrim(getenv('HOME'), '/');
	}

}
