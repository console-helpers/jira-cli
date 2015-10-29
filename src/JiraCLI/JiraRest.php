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


use GuzzleHttp\ClientInterface;

class JiraRest
{

	/**
	 * URL.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Username.
	 *
	 * @var string
	 */
	protected $user;

	/**
	 * Password.
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Guzzle client.
	 *
	 * @var ClientInterface
	 */
	protected $guzzleClient;

	/**
	 * Creates Jira Rest client.
	 *
	 * @param string          $url           Url.
	 * @param string          $user          Username.
	 * @param string          $password      Password.
	 * @param ClientInterface $guzzle_client Guzzle client.
	 */
	public function __construct($url, $user, $password, ClientInterface $guzzle_client)
	{
		$this->url = $url;
		$this->guzzleClient = $guzzle_client;

		if ( !$url ) {
			throw new \RuntimeException('Url "' . $url . '" is invalid');
		}

		$this->user = $user;
		$this->password = $password;
	}

	/**
	 * Returns issue details.
	 *
	 * @param string $issue_key Issue key.
	 *
	 * @return array
	 */
	public function getIssue($issue_key)
	{
		return $this->get('issue/' . $issue_key);
	}

	/**
	 * Returns attachment details.
	 *
	 * @param integer $attachment_id Attachment ID.
	 *
	 * @return array
	 */
	public function getAttachment($attachment_id)
	{
		return $this->get('attachment/' . $attachment_id);
	}

	/**
	 * Downloads attachment.
	 *
	 * @param integer $attachment_id Attachment ID.
	 *
	 * @return string
	 */
	public function getAttachmentContent($attachment_id)
	{
		$attachment_data = $this->getAttachment($attachment_id);

		$response = $this->guzzleClient->get($attachment_data['content'], $this->getOptions());

		if ( $response->getStatusCode() == 200 ) {
			return $response->getBody();
		}

		return false;
	}

	/**
	 * Performs GET API request.
	 *
	 * @param string $command Command.
	 *
	 * @return array
	 */
	protected function get($command)
	{
		$response = $this->guzzleClient->get($this->getUrl($command), $this->getOptions());

		if ( $response->getStatusCode() == 200 ) {
			return json_decode($response->getBody(), true);
		}

		return array();
	}

	/**
	 * Returns url for the API call.
	 *
	 * @param string $command Command.
	 * @param array  $params  Command params.
	 *
	 * @return string
	 */
	protected function getUrl($command, array $params = array())
	{
		return $this->url . '/rest/api/2/' . $command;
	}

	/**
	 * Returns connection options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		if ( !$this->user ) {
			return array();
		}

		return array('auth' => array($this->user, $this->password));
	}

	/**
	 * Checks if an issue key is valid.
	 *
	 * @param string $issue_key Issue key.
	 *
	 * @return boolean
	 */
	public function isValidIssueKey($issue_key)
	{
		return preg_match('/^([A-Z]+-[0-9]+)$/', $issue_key);
	}

}
