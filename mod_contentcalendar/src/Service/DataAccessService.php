<?php
/*
 *  package: Joomill Content Calendar FREE
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Contentcalendar\Administrator\Service;

use DateTime;
use DateTimeZone;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

defined('_JEXEC') or die;

/**
 * Data Access Service for Content Calendar Module
 *
 * Handles all database operations including article retrieval, updates, and permission checks.
 * Queries are built with quoted identifiers and bound parameters so they work across the
 * database drivers Joomla supports.
 *
 * @since  1.0.0
 */
class DataAccessService
{
	/**
	 * Database interface
	 *
	 * @var    DatabaseInterface
	 * @since  1.0.0
	 */
	private $db;

	/**
	 * Constructor
	 *
	 * @param   DatabaseInterface  $db  Database interface
	 *
	 * @since   1.0.0
	 */
	public function __construct(DatabaseInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * Retrieve articles for a specific month and categories from the database
	 *
	 * @param   array  $categories  Array of category IDs to filter articles by
	 * @param   int    $month       Month number (1-12) to retrieve articles for
	 * @param   int    $year        Year to retrieve articles for
	 *
	 * @return  array  Array of article objects
	 *
	 * @since   2.0.0
	 */
	public function getArticlesForMonth($categories, $month, $year)
	{
		$month = (int) $month;
		$year  = (int) $year;

		// Inclusive start and exclusive end of the month. This avoids database
		// specific date functions (YEAR()/MONTH()) and stays index friendly.
		$start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
		$end   = date('Y-m-d 00:00:00', mktime(0, 0, 0, $month + 1, 1, $year));

		return $this->getArticlesBetween($categories, $start, $end);
	}

	/**
	 * Retrieve articles for a specific date range
	 *
	 * @param   array   $categories  Category IDs filter
	 * @param   string  $startDate   Inclusive start date (Y-m-d)
	 * @param   string  $endDate     Inclusive end date (Y-m-d)
	 *
	 * @return  array
	 *
	 * @since   2.0.0
	 */
	public function getArticlesForDateRange($categories, $startDate, $endDate)
	{
		$start = $startDate . ' 00:00:00';

		// Exclusive end at midnight after the requested end date keeps it inclusive.
		$end = date('Y-m-d 00:00:00', strtotime($endDate . ' +1 day'));

		return $this->getArticlesBetween($categories, $start, $end);
	}

	/**
	 * Retrieve published/archived articles whose publish_up falls in [start, end)
	 *
	 * @param   array   $categories  Category IDs filter
	 * @param   string  $start       Inclusive start datetime (Y-m-d H:i:s)
	 * @param   string  $end         Exclusive end datetime (Y-m-d H:i:s)
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	private function getArticlesBetween($categories, string $start, string $end): array
	{
		try
		{
			$db    = $this->db;
			$query = $db->getQuery(true);

			$query->select(
				[
					$db->quoteName('a.id'),
					$db->quoteName('a.title'),
					$db->quoteName('a.publish_up'),
					$db->quoteName('a.state'),
					$db->quoteName('a.catid'),
					$db->quoteName('a.created_by'),
					$db->quoteName('a.note'),
					$db->quoteName('a.metadesc', 'meta_desc'),
					$db->quoteName('a.introtext', 'intro_text'),
					$db->quoteName('u.name', 'author_name'),
					$db->quoteName('c.title', 'category_title'),
				]
			)
				->from($db->quoteName('#__content', 'a'))
				->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'))
				->join('LEFT', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
				->where($db->quoteName('a.publish_up') . ' >= :startDate')
				->where($db->quoteName('a.publish_up') . ' < :endDate')
				->whereIn($db->quoteName('a.state'), [1, 2])
				->bind(':startDate', $start)
				->bind(':endDate', $end)
				->order($db->quoteName('a.publish_up') . ' ASC');

			// Apply category filter only when categories are provided
			if (!empty($categories))
			{
				$catIds = array_map('intval', is_array($categories) ? $categories : [$categories]);
				$query->whereIn($db->quoteName('a.catid'), $catIds);
			}

			$db->setQuery($query);
			$articles = $db->loadObjectList() ?: [];

			$this->localizeDates($articles);

			return $this->attachTags($articles);
		}
		catch (Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'mod_contentcalendar');

			return [];
		}
	}

	/**
	 * Attach tag names and ids to a list of articles
	 *
	 * Replaces a database specific GROUP_CONCAT with a single portable query that
	 * is aggregated in PHP, keeping the main article query free of grouping.
	 *
	 * @param   array  $articles  Article objects (each must expose an id property)
	 *
	 * @return  array  The same articles with tag_names and tag_ids populated
	 *
	 * @since   1.0.0
	 */
	private function attachTags(array $articles): array
	{
		if (empty($articles))
		{
			return $articles;
		}

		$ids  = array_map(static fn ($a) => (int) $a->id, $articles);
		$rows = [];

		try
		{
			$db        = $this->db;
			$typeAlias = 'com_content.article';

			$query = $db->getQuery(true);
			$query->select(
				[
					$db->quoteName('tm.content_item_id'),
					$db->quoteName('t.id', 'tag_id'),
					$db->quoteName('t.title', 'tag_title'),
				]
			)
				->from($db->quoteName('#__contentitem_tag_map', 'tm'))
				->join('INNER', $db->quoteName('#__tags', 't'), $db->quoteName('t.id') . ' = ' . $db->quoteName('tm.tag_id'))
				->where($db->quoteName('tm.type_alias') . ' = :typeAlias')
				->where($db->quoteName('t.published') . ' = 1')
				->whereIn($db->quoteName('tm.content_item_id'), $ids)
				->order($db->quoteName('t.title') . ' ASC')
				->bind(':typeAlias', $typeAlias);

			$db->setQuery($query);
			$rows = $db->loadObjectList() ?: [];
		}
		catch (Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'mod_contentcalendar');
		}

		// Group the tags by article id
		$names  = [];
		$tagIds = [];

		foreach ($rows as $row)
		{
			$cid            = (int) $row->content_item_id;
			$names[$cid][]  = $row->tag_title;
			$tagIds[$cid][] = (int) $row->tag_id;
		}

		foreach ($articles as $article)
		{
			$cid                = (int) $article->id;
			$article->tag_names = isset($names[$cid]) ? implode(', ', $names[$cid]) : '';
			$article->tag_ids   = isset($tagIds[$cid]) ? implode(',', $tagIds[$cid]) : '';
		}

		return $articles;
	}

	/**
	 * Convert each article's UTC publish_up into the display timezone
	 *
	 * Joomla stores publish_up in UTC. Without conversion the articles would be
	 * bucketed on the wrong calendar day and shown with the wrong time for users
	 * whose timezone differs from the server. The original UTC value is used to
	 * compute a reliable is_future flag, after which publish_up is replaced by
	 * the local wall-clock value the templates and grouping render.
	 *
	 * @param   array  $articles  Article objects (modified in place)
	 *
	 * @return  void
	 *
	 * @since   1.0.1
	 */
	private function localizeDates(array $articles): void
	{
		if (empty($articles))
		{
			return;
		}

		$app  = Factory::getApplication();
		$user = $app->getIdentity();
		$tz   = ($user && $user->getParam('timezone')) ? $user->getParam('timezone') : $app->get('offset', 'UTC');

		try
		{
			$zone = new DateTimeZone($tz);
		}
		catch (Exception $e)
		{
			$zone = new DateTimeZone('UTC');
		}

		$now = Factory::getDate('now');

		foreach ($articles as $article)
		{
			if (empty($article->publish_up))
			{
				$article->is_future = false;

				continue;
			}

			// publish_up is stored in UTC; compare the real instant for is_future.
			$date               = Factory::getDate($article->publish_up);
			$article->is_future = $date > $now;

			// Replace publish_up with the local wall-clock value for display/grouping.
			$article->publish_up = $date->setTimezone($zone)->format('Y-m-d H:i:s');
		}
	}

	/**
	 * Update article publication date in the database
	 *
	 * Updates the publish_up field for the specified article while preserving the original
	 * time component. Validates article existence and handles database errors gracefully.
	 *
	 * @param   int     $articleId  The article ID to update
	 * @param   string  $newDate    The new publication date in Y-m-d format
	 *
	 * @return  bool    True on successful update, false on failure
	 *
	 * @since   2.0.0
	 */
	public function updateArticleDate($articleId, $newDate)
	{
		$articleId = (int) $articleId;

		// Reject anything that is not a plain Y-m-d date before touching the database.
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $newDate))
		{
			return false;
		}

		try
		{
			$db = $this->db;

			// First, get the current article data
			$query = $db->getQuery(true);
			$query->select($db->quoteName(['publish_up', 'publish_down']))
				->from($db->quoteName('#__content'))
				->where($db->quoteName('id') . ' = :id')
				->bind(':id', $articleId, ParameterType::INTEGER);

			$db->setQuery($query);
			$article = $db->loadObject();

			if (!$article)
			{
				return false;
			}

			// Keep the original time component, only move the date
			$currentDateTime = new DateTime($article->publish_up);
			$newDateTime     = $newDate . ' ' . $currentDateTime->format('H:i:s');

			$query = $db->getQuery(true);
			$query->update($db->quoteName('#__content'))
				->set($db->quoteName('publish_up') . ' = :publishUp')
				->where($db->quoteName('id') . ' = :id')
				->bind(':publishUp', $newDateTime)
				->bind(':id', $articleId, ParameterType::INTEGER);

			$db->setQuery($query);

			return $db->execute();
		}
		catch (Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'mod_contentcalendar');

			return false;
		}
	}

	/**
	 * Check if user has permission to edit the specified article
	 *
	 * Validates user permissions for editing articles based on core permissions,
	 * category-specific permissions, and article ownership. Handles super user
	 * privileges and gracefully handles database errors.
	 *
	 * @param   User  $user       The user object to check permissions for
	 * @param   int   $articleId  The article ID to check edit permissions against
	 *
	 * @return  bool  True if user can edit the article, false otherwise
	 *
	 * @since   2.0.0
	 */
	public function canEditArticle($user, $articleId)
	{
		// Super users can edit everything
		if ($user->authorise('core.admin'))
		{
			return true;
		}

		// Check if user can edit articles in general
		if (!$user->authorise('core.edit', 'com_content'))
		{
			return false;
		}

		$articleId = (int) $articleId;

		try
		{
			$db    = $this->db;
			$query = $db->getQuery(true);
			$query->select($db->quoteName(['catid', 'created_by', 'state']))
				->from($db->quoteName('#__content'))
				->where($db->quoteName('id') . ' = :id')
				->bind(':id', $articleId, ParameterType::INTEGER);

			$db->setQuery($query);
			$article = $db->loadObject();

			if (!$article)
			{
				return false;
			}

			// Check category permissions
			if (!$user->authorise('core.edit', 'com_content.category.' . $article->catid))
			{
				// Check if user can edit own articles
				if ($article->created_by == $user->id
					&& $user->authorise('core.edit.own', 'com_content.category.' . $article->catid))
				{
					return true;
				}

				return false;
			}

			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
}
