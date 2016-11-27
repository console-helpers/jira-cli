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


use chobie\Jira\Api;
use chobie\Jira\Api\Result;
use chobie\Jira\IssueType;
use Doctrine\Common\Cache\CacheProvider;

class JiraApi extends Api
{

	const CACHE_DURATION_ONE_MONTH = 2592000;

	/**
	 * Cache.
	 *
	 * @var CacheProvider
	 */
	protected $cache;

	/**
	 * Sets cache.
	 *
	 * @param CacheProvider $cache Cache.
	 *
	 * @return void
	 */
	public function setCache(CacheProvider $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * Get fields definitions.
	 *
	 * @param integer $cache_duration Cache duration.
	 *
	 * @return array
	 */
	public function getFields($cache_duration = 0)
	{
		$cache_key = __METHOD__ . '()';
		$cached_value = $this->cache->fetch($cache_key);

		if ( $cached_value === false ) {
			$cached_value = parent::getFields();
			$this->cache->save($cache_key, $cached_value, $cache_duration);
		}

		return $cached_value;
	}

	/**
	 * Get available issue types.
	 *
	 * @param integer $cache_duration Cache duration.
	 *
	 * @return IssueType[]
	 */
	public function getIssueTypes($cache_duration = 0)
	{
		$cache_key = __METHOD__ . '()';
		$cached_value = $this->cache->fetch($cache_key);

		if ( $cached_value === false ) {
			$cached_value = parent::getIssueTypes();
			$this->cache->save($cache_key, $cached_value, $cache_duration);
		}

		return $cached_value;
	}

	/**
	 * Get versions of a project.
	 *
	 * @param string  $project_key    Project key.
	 * @param integer $cache_duration Cache duration.
	 *
	 * @return array|false
	 */
	public function getVersions($project_key, $cache_duration = 0)
	{
		$cache_key = __METHOD__ . '(' . $project_key . ')';
		$cached_value = $this->cache->fetch($cache_key);

		if ( $cached_value === false ) {
			$cached_value = parent::getVersions($project_key);
			$this->cache->save($cache_key, $cached_value, $cache_duration);
		}

		return $cached_value;
	}

	/**
	 * Returns possible link names.
	 *
	 * @return array
	 */
	public function getProjectKeys()
	{
		$cache_key = 'project_keys';
		$cached_value = $this->cache->fetch($cache_key);

		if ( $cached_value === false ) {
			$cached_value = array();
			$response = $this->getProjects();

			if ( $response instanceof Result ) {
				$response = $response->getResult();
			}

			foreach ( $response as $project_data ) {
				$cached_value[] = $project_data['key'];
			}

			$this->cache->save($cache_key, $cached_value);
		}

		return $cached_value;
	}

	/**
	 * Returns issue link type names.
	 *
	 * @return array
	 */
	public function getIssueLinkTypeNames()
	{
		$cache_key = 'issue_link_type_names';
		$cached_value = $this->cache->fetch($cache_key);

		if ( $cached_value === false ) {
			$cached_value = array();
			$response = $this->api(self::REQUEST_GET, '/rest/api/2/issueLinkType', array(), true);

			foreach ( $response['issueLinkTypes'] as $link_type_data ) {
				$cached_value[] = $link_type_data['name'];
			}

			$this->cache->save($cache_key, $cached_value);
		}

		return $cached_value;
	}

	/**
	 * Returns project component mapping (id to name).
	 *
	 * @param string  $project_key    Project key.
	 * @param integer $cache_duration Cache duration.
	 *
	 * @return array
	 */
	public function getProjectComponentMapping($project_key, $cache_duration = 0)
	{
		$cache_key = 'project_components[' . $project_key . ']';
		$cached_value = $this->cache->fetch($cache_key);

		if ( $cached_value === false ) {
			$cached_value = array();
			$project_components = $this->getProjectComponents($project_key);

			foreach ( $project_components as $project_component_data ) {
				$cached_value[$project_component_data['id']] = $project_component_data['name'];
			}

			$this->cache->save($cache_key, $cached_value, $cache_duration);
		}

		return $cached_value;
	}

}
