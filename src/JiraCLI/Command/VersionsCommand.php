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


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionsCommand extends AbstractCommand
{

	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this
			->setName('versions')
			->setDescription('Shows version usage across projects');
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$versions = array();

		$this->io->write('Getting projects ... ');
		$project_keys = $this->getProjectKeys();
		$this->io->writeln('done (' . count($project_keys) . ' found)');

		foreach ( $project_keys as $project_key ) {
			$this->io->write('Getting project <info>' . $project_key . '</info> versions ... ');
			$project_versions = $this->getProjectVersionsRaw($project_key);
			$this->io->writeln('done (' . count($project_versions) . ' found)');

			foreach ( $project_versions as $project_version_data ) {
				$project_version = $project_version_data['name'];

				// Interested only in final releases.
				if ( strpos($project_version, '-') === false ) {
					$versions[$project_version] = true;
				}
			}
		}

		$versions = array_keys($versions);
		usort($versions, 'version_compare');

		$this->io->writeln(array('', 'Versions:'));

		foreach ( $versions as $version ) {
			$this->io->writeln(' * ' . $version);
		}
	}

	/**
	 * Returns raw project versions.
	 *
	 * @param string $project_key Project key.
	 *
	 * @return array
	 */
	protected function getProjectVersionsRaw($project_key)
	{
		$cache_key = 'project_versions_raw[' . $project_key . ']';
		$cached_value = $this->cache->fetch($cache_key);

		if ( $cached_value === false ) {
			$cached_value = $this->jiraApi->getVersions($project_key);
			$this->cache->save($cache_key, $cached_value, 2592000); // Cache for 1 month.
		}

		return $cached_value;
	}

}
