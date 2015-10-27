<?php
/**
 * This file is part of the jira-cli library.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @copyright Alexander Obuhovich <aik.bold@gmail.com>
 * @link      https://github.com/aik099/jira-cli
 */

namespace Tests\aik099\JiraCLI;


class WorkingDirectoryTest extends WorkingDirectoryAwareTestCase
{

	public function testWorkingDirectoryCreation()
	{
		$expected_working_directory = $this->getExpectedWorkingDirectory();

		$actual_working_directory = $this->getWorkingDirectory();
		$this->assertEquals($expected_working_directory, $actual_working_directory);
		$this->assertFileExists($expected_working_directory);

		// If directory is created, when it exists, them this would trigger a warning.
		$this->getWorkingDirectory();
	}

	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage The HOME environment variable must be set to run correctly
	 */
	public function testBrokenLinuxEnvironment()
	{
		putenv('HOME=');
		$this->getWorkingDirectory();
	}

	/**
	 * @runInSeparateProcess
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage The APPDATA environment variable must be set to run correctly
	 */
	public function testBrokenWindowsEnvironment()
	{
		putenv('HOME=');
		define('PHP_WINDOWS_VERSION_MAJOR', 5);

		$this->getWorkingDirectory();
	}

	/**
	 * Returns correct working directory.
	 *
	 * @return string
	 */
	protected function getExpectedWorkingDirectory()
	{
		return getenv('HOME') . '/.jira-cli';
	}

}
