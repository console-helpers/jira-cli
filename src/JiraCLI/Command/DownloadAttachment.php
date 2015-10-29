<?php
/**
 * This file is part of the Jira-CLI library.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @copyright Alexander Obuhovich <aik.bold@gmail.com>
 * @link      https://github.com/console-helpers/jira-cli
 */

namespace aik099\JiraCLI\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadAttachment extends AbstractCommand
{

	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this
			->setName('download-attachment')
			->setDescription('Downloads issue attachments')
			->addArgument('issue', InputArgument::REQUIRED, 'Issue key or url')
			->addOption(
				'index',
				'i',
				InputOption::VALUE_IS_ARRAY + InputOption::VALUE_OPTIONAL,
				'Attachment index to download'
			);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$issue_key = $this->getIssueKey();
		$issue_data = $this->jiraRest->getIssue($issue_key);

		$attachments = $issue_data['fields']['attachment'];

		// Show attachments.
		$this->io->writeln('<info>Issue ' . $issue_key . ' attachments:</info>');

		foreach ( $attachments as $index => $attachment_data ) {
			$this->io->writeln($index . '. ' . $attachment_data['filename']);
		}

		$indexes = $this->io->getOption('index');

		if ( count($indexes) === 0 ) {
			$indexes = array_keys($attachments);
		}

		// Download attachments.
		$this->io->writeln('');
		$this->io->writeln('Downloading all attachments to <info>' . getcwd() . '</info>');

		foreach ( $attachments as $index => $attachment_data ) {
			if ( !in_array($index, $indexes) ) {
				continue;
			}

			$this->io->write('- ' . $attachment_data['filename'] . ' ... ');
			file_put_contents(
				$attachment_data['filename'],
				$this->jiraRest->getAttachmentContent($attachment_data['id'])
			);
			$this->io->writeln('done');
		}
	}

	/**
	 * Returns issue key.
	 *
	 * @return string
	 * @throws \InvalidArgumentException When issue key is invalid.
	 */
	protected function getIssueKey()
	{
		$issue = $this->io->getArgument('issue');

		if ( $this->jiraRest->isValidIssueKey($issue) ) {
			return $issue;
		}

		throw new \InvalidArgumentException('The issue key is invalid');
	}

}
