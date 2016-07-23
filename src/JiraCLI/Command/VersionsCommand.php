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
		$projects = $this->jiraApi->getProjects()->getResult();
		$this->io->writeln('done (' . count($projects) . ' found)');

		foreach ( $projects as $index => $project_data ) {
			$project_key = $project_data['key'];

			$this->io->write('Getting project <info>#' . $index . '</info> versions ... ');
			$project_versions = $this->jiraApi->getVersions($project_key);
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

}
