<?php
/**
 * This file is part of the jira-cli library.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @copyright Alexander Obuhovich <aik.bold@gmail.com>
 * @link      https://github.com/aik099/jira-cli
 */

namespace aik099\JiraCli\Command;


use aik099\JiraCli\JiraRest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadAttachment extends Command
{

	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this
			->setName('download-attachment')
			->setDescription('Downloads issue attachments');

		$this->addArgument('issue', InputArgument::REQUIRED, 'Issue key or url');
		$this->addOption(
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
		$jira_rest = $this->getJiraRest();

		$issue_key = $this->getIssueKey($input);
		$issue_data = $jira_rest->getIssue($issue_key);

		$attachments = $issue_data['fields']['attachment'];

		// Show attachments.
		$output->writeln('<info>Issue ' . $issue_key . ' attachments:</info>');

		foreach ( $attachments as $index => $attachment_data ) {
			$output->writeln($index . '. ' . $attachment_data['filename']);
		}

		$indexes = $input->getOption('index');

		if ( count($indexes) === 0 ) {
			$indexes = array_keys($attachments);
		}

		// Download attachments.
		$output->writeln('');
		$output->writeln('Downloading all attachments to <info>' . getcwd() . '</info>');

		foreach ( $attachments as $index => $attachment_data ) {
			if ( !in_array($index, $indexes) ) {
				continue;
			}

			$output->write('- ' . $attachment_data['filename'] . ' ... ');
			file_put_contents($attachment_data['filename'], $jira_rest->getAttachmentContent($attachment_data['id']));
			$output->writeln('done');
		}
	}

	/**
	 * Returns issue key.
	 *
	 * @param InputInterface $input Input interface.
	 *
	 * @return string
	 * @throws \InvalidArgumentException When issue key is invalid.
	 */
	protected function getIssueKey(InputInterface $input)
	{
		$jira_rest = $this->getJiraRest();

		$issue = $input->getArgument('issue');

		if ( $jira_rest->isValidIssueKey($issue) ) {
			return $issue;
		}

		throw new \InvalidArgumentException('The issue key is invalid');
	}

	/**
	 * Returns REST client for Jira API calls.
	 *
	 * @return JiraRest
	 */
	protected function getJiraRest()
	{
		return $this->getApplication()->getObject('jira-rest');
	}

}
