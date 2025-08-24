<?php
/*
 *  package: Content Calendar FREE
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Contentcalendar\Administrator\Service;

use DateTime;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

/**
 * Data Access Service for Content Calendar Module
 *
 * Handles all database operations including article retrieval, updates, and permission checks.
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
	 * Fetches published articles from specified categories for the given month and year.
	 * Includes article metadata such as title, publication date, state, category, and author.
	 * Supports filtering by publication state and future article visibility.
	 *
	 * @param   array  $categories  Array of category IDs to filter articles by
	 * @param   int    $month       Month number (1-12) to retrieve articles for
	 * @param   int    $year        Year to retrieve articles for
	 *
	 * @return  array  Array of article objects with id, title, publish_up, state, catid, created_by, author_name
	 *
	 * @throws  Exception  When database operations fail
	 * @since   2.0.0
	 *
	 */
	public function getArticlesForMonth($categories, $month, $year)
	{
		try
		{
			$query = $this->db->getQuery(true);

			// Build the query with additional joins for category and tags, and note fields
			$query->select([
				'a.id',
				'a.title',
				'a.publish_up',
				'a.state',
				'a.catid',
				'a.created_by',
				'a.note AS note',
				'u.name AS author_name',
				'c.title AS category_title',
				'a.metadesc AS meta_desc',
				'a.introtext AS intro_text',
				"GROUP_CONCAT(DISTINCT t.title ORDER BY t.title SEPARATOR ', ') AS tag_names",
				"GROUP_CONCAT(DISTINCT tm.tag_id ORDER BY tm.tag_id SEPARATOR ',') AS tag_ids"
			])
				->from('#__content AS a')
				->leftJoin('#__users AS u ON a.created_by = u.id')
				->leftJoin('#__categories AS c ON a.catid = c.id')
				->leftJoin(
					"#__contentitem_tag_map AS tm ON tm.content_item_id = a.id AND tm.type_alias = 'com_content.article'"
				)
				->leftJoin('#__tags AS t ON t.id = tm.tag_id AND t.published = 1')
				->where('YEAR(a.publish_up) = ' . (int) $year)
				->where('MONTH(a.publish_up) = ' . (int) $month);

			// Apply category filter only when categories are provided
			if (!empty($categories))
			{
				// Ensure categories is an array of integers
				$catIds = is_array($categories) ? $categories : [$categories];
				$catIds = array_map('intval', $catIds);
				$query->where('a.catid IN (' . implode(',', $catIds) . ')');
			}

			// Always include published and archived articles, including future dates
			$query->where('a.state IN (1, 2)');

			$query->group('a.id');
			$query->order('a.publish_up ASC');

			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return [];
		}
	}

	/**
	 * Retrieve articles for a specific date range
	 *
	 * @param   array   $categories  Category IDs filter
	 * @param   string  $startDate   Inclusive start date (Y-m-d)
	 * @param   string  $endDate     Inclusive end date (Y-m-d)
	 *
	 * @return array
	 */
	public function getArticlesForDateRange($categories, $startDate, $endDate)
	{
		try
		{
			$query = $this->db->getQuery(true);

			$query->select([
				'a.id',
				'a.title',
				'a.publish_up',
				'a.state',
				'a.catid',
				'a.created_by',
				'a.note AS note',
				'u.name AS author_name',
				'c.title AS category_title',
				'a.metadesc AS meta_desc',
				'a.introtext AS intro_text',
				"GROUP_CONCAT(DISTINCT t.title ORDER BY t.title SEPARATOR ', ') AS tag_names",
				"GROUP_CONCAT(DISTINCT tm.tag_id ORDER BY tm.tag_id SEPARATOR ',') AS tag_ids"
			])
				->from('#__content AS a')
				->leftJoin('#__users AS u ON a.created_by = u.id')
				->leftJoin('#__categories AS c ON a.catid = c.id')
				->leftJoin(
					"#__contentitem_tag_map AS tm ON tm.content_item_id = a.id AND tm.type_alias = 'com_content.article'"
				)
				->leftJoin('#__tags AS t ON t.id = tm.tag_id AND t.published = 1')
				->where('DATE(a.publish_up) >= ' . $this->db->quote($startDate))
				->where('DATE(a.publish_up) <= ' . $this->db->quote($endDate));

			if (!empty($categories))
			{
				$catIds = is_array($categories) ? $categories : [$categories];
				$catIds = array_map('intval', $catIds);
				$query->where('a.catid IN (' . implode(',', $catIds) . ')');
			}

			// Always include published and archived articles, including future dates
			$query->where('a.state IN (1, 2)');

			$query->group('a.id');
			$query->order('a.publish_up ASC');

			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return [];
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
	 * @throws  Exception  When database operations fail
	 * @since   2.0.0
	 *
	 */
	public function updateArticleDate($articleId, $newDate)
	{
		try
		{
			// First, get the current article data
			$query = $this->db->getQuery(true);
			$query->select('publish_up, publish_down')
				->from('#__content')
				->where('id = ' . (int) $articleId);

			$this->db->setQuery($query);
			$article = $this->db->loadObject();

			if (!$article)
			{
				return false;
			}

			// Get the current time part from the existing publish_up
			$currentDateTime = new DateTime($article->publish_up);
			$timeString      = $currentDateTime->format('H:i:s');

			// Create new datetime with the same time but new date
			$newDateTime = $newDate . ' ' . $timeString;

			// Update the article
			$query = $this->db->getQuery(true);
			$query->update('#__content')
				->set('publish_up = ' . $this->db->quote($newDateTime))
				->where('id = ' . (int) $articleId);

			$this->db->setQuery($query);
			$result = $this->db->execute();

			return $result;
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

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
	 * @throws  Exception  When database operations fail
	 * @since   2.0.0
	 *
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

		try
		{
			$query = $this->db->getQuery(true);
			$query->select('catid, created_by, state')
				->from('#__content')
				->where('id = ' . (int) $articleId);

			$this->db->setQuery($query);
			$article = $this->db->loadObject();

			if (!$article)
			{
				return false;
			}

			// Check category permissions
			if (!$user->authorise('core.edit', 'com_content.category.' . $article->catid))
			{
				// Check if user can edit own articles
				if ($article->created_by == $user->id && $user->authorise(
						'core.edit.own',
						'com_content.category.' . $article->catid
					))
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