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


use GuzzleHttp\Client;
use Pimple\Container;

class DIContainer extends Container
{

	/**
	 * {@inheritdoc}
	 */
	public function __construct(array $values = array())
	{
		parent::__construct($values);

		$this['config-file'] = '{home}/config.json';

		$this['config'] = function ($c) {
			return Config::createFromFile($c['config-file']);
		};

		$this['jira-rest'] = function ($c) {
			/** @var Config $config */
			$config = $c['config'];

			return new JiraRest(
				$config->get('jira-url'),
				$config->get('jira-user'),
				$config->get('jira-password'),
				new Client()
			);
		};
	}

}
