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


use chobie\Jira\Api\Authentication\Anonymous;
use chobie\Jira\Api\Authentication\AuthenticationInterface;
use chobie\Jira\Api\Client\ClientInterface;
use Guzzle\Http\ClientInterface as GuzzleClientInterface;

class GuzzleClient implements ClientInterface
{

	/**
	 * Guzzle client.
	 *
	 * @var GuzzleClientInterface
	 */
	private $_guzzleClient;

	/**
	 * Create guzzle client instance.
	 *
	 * @param GuzzleClientInterface $guzzle_client Guzzle client.
	 */
	public function __construct(GuzzleClientInterface $guzzle_client)
	{
		$this->_guzzleClient = $guzzle_client;
	}

	/**
	 * Send request to the api server.
	 *
	 * @param string                  $method     Method.
	 * @param string                  $url        Url.
	 * @param array                   $data       Data.
	 * @param string                  $endpoint   Endpoint.
	 * @param AuthenticationInterface $credential Credential.
	 * @param boolean                 $isFile     Is file.
	 * @param boolean                 $debug      Is debug.
	 *
	 * @return array|string
	 */
	public function sendRequest(
		$method,
		$url,
		$data = array(),
		$endpoint,
		AuthenticationInterface $credential,
		$isFile = false,
		$debug = false
	) {
		if ( $isFile ) {
			$headers = array('X-Atlassian-Token: nocheck');
		}
		else {
			$headers = array('Content-Type: application/json;charset=UTF-8');
		}

		$options = array();

		if ( !($credential instanceof Anonymous) ) {
			$options = array(
				'auth' => array($credential->getId(), $credential->getPassword()),
			);
		}

		if ( $method == 'GET' ) {
			$options['query'] = $data;
		}

		if ( $debug ) {
			$options['debug'] = true;
		}

		if ( $method == 'POST' ) {
			if ( $isFile ) {
				$options['body'] = $data;
			}
			else {
				$options['json'] = $data;
			}
		}
		elseif ( $method == 'PUT' ) {
			$options['json'] = $data;
		}

		$response = $this->_guzzleClient->createRequest($method, $endpoint . $url, $headers, null, $options)->send();

		return $response->getBody(true);
	}

}
