<?php
/*
 *  package: Joomill Content Calendar FREE
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Contentcalendar\Administrator\Service;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * Business Logic Service for Content Calendar Module
 *
 * Handles all business logic operations including validation, calculations, and data processing.
 *
 * @since  1.0.0
 */
class BusinessLogicService
{
	/**
	 * Validate and sanitize month and year parameters
	 *
	 * Ensures month and year values are within acceptable ranges. Falls back to
	 * current date values if parameters are invalid. Month must be 1-12, year
	 * must be between 1970-2100 to prevent potential issues with date calculations.
	 *
	 * @param   int  $month  Month number to validate (expected range: 1-12)
	 * @param   int  $year   Year to validate (expected range: 1970-2100)
	 *
	 * @return  array  Associative array with validated 'month' and 'year' keys
	 *
	 * @since   1.0.0
	 */
	public function validateMonthYear($month, $year)
	{
		// Ensure valid month/year
		if ($month < 1 || $month > 12)
		{
			$month = date('n');
		}
		if ($year < 1970 || $year > 2100)
		{
			$year = date('Y');
		}

		return [
			'month' => $month,
			'year'  => $year
		];
	}

	/**
	 * Organize articles by day of the month for calendar display
	 *
	 * Groups article objects by their publication day within the month.
	 * Extracts the day number from each article's publish_up date and creates
	 * an associative array where keys are day numbers and values are arrays of articles.
	 *
	 * @param   array  $articles  Array of article objects with publish_up property
	 *
	 * @return  array  Associative array where keys are day numbers (1-31) and values are arrays of article objects
	 *
	 * @since   2.0.0
	 */
	public function organizeArticlesByDay($articles)
	{
		$articles_by_day = [];

		foreach ($articles as $article)
		{
			$day = date('j', strtotime($article->publish_up));
			if (!isset($articles_by_day[$day]))
			{
				$articles_by_day[$day] = [];
			}
			$articles_by_day[$day][] = $article;
		}

		return $articles_by_day;
	}

	/**
	 * Prepare complete calendar data array for template rendering
	 *
	 * Assembles all necessary data for calendar display including navigation data,
	 * month metadata, and organized articles. Calculates calendar-specific information
	 * such as month name, days in month, and first day of week for proper grid layout.
	 *
	 * @param   int    $month            Current month number (1-12)
	 * @param   int    $year             Current year (4-digit format)
	 * @param   array  $articles_by_day  Articles organized by day number (from organizeArticlesByDay)
	 *
	 * @return  array  Complete calendar data array with navigation, metadata, and articles
	 *
	 * @since   2.0.0
	 */
	public function prepareCalendarData($month, $year, $articles_by_day)
	{
		$navigation = $this->calculateNavigation($month, $year);

		$w              = date('w', mktime(0, 0, 0, $month, 1, $year));
		$firstDayOffset = ($w + 6) % 7;

		return [
			'current_month'     => $month,
			'current_year'      => $year,
			'prev_month'        => $navigation['prev_month'],
			'prev_year'         => $navigation['prev_year'],
			'next_month'        => $navigation['next_month'],
			'next_year'         => $navigation['next_year'],
			'articles_by_day'   => $articles_by_day,
			'month_name'        => (function () use ($month, $year) {
				// Localize month name via Joomla language; fallback to PHP short month if missing
				$keys = [
					1  => 'JANUARY',
					2  => 'FEBRUARY',
					3  => 'MARCH',
					4  => 'APRIL',
					5  => 'MAY',
					6  => 'JUNE',
					7  => 'JULY',
					8  => 'AUGUST',
					9  => 'SEPTEMBER',
					10 => 'OCTOBER',
					11 => 'NOVEMBER',
					12 => 'DECEMBER'
				];
				if (isset($keys[$month]))
				{
					$t = Text::_($keys[$month]);
					if ($t !== $keys[$month])
					{
						return $t;
					}
				}

				return date('M', mktime(0, 0, 0, $month, 1, $year));
			})(),
			'days_in_month'     => date('t', mktime(0, 0, 0, $month, 1, $year)),
			'first_day_of_week' => $firstDayOffset
		];
	}

	/**
	 * Calculate previous and next month/year navigation data for calendar controls
	 *
	 * Computes the previous and next month/year combinations for calendar navigation.
	 * Handles year transitions when moving from January to December and vice versa.
	 * Used for generating navigation links in the calendar interface.
	 *
	 * @param   int  $month  Current month number (1-12)
	 * @param   int  $year   Current year (4-digit format)
	 *
	 * @return  array  Associative array with prev_month, prev_year, next_month, next_year keys
	 *
	 * @since   2.0.0
	 */
	public function calculateNavigation($month, $year)
	{
		// Calculate previous month/year
		$prev_month = $month - 1;
		$prev_year  = $year;
		if ($prev_month < 1)
		{
			$prev_month = 12;
			$prev_year--;
		}

		// Calculate next month/year
		$next_month = $month + 1;
		$next_year  = $year;
		if ($next_month > 12)
		{
			$next_month = 1;
			$next_year++;
		}

		return [
			'prev_month' => $prev_month,
			'prev_year'  => $prev_year,
			'next_month' => $next_month,
			'next_year'  => $next_year
		];
	}

	/**
	 * Organize articles by exact date (Y-m-d) for range-based views
	 *
	 * @param   array  $articles
	 *
	 * @return array  [ 'Y-m-d' => [articles...] ]
	 */
	public function organizeArticlesByDate($articles)
	{
		$map = [];
		foreach ($articles as $article)
		{
			$key = date('Y-m-d', strtotime($article->publish_up));
			if (!isset($map[$key]))
			{
				$map[$key] = [];
			}
			$map[$key][] = $article;
		}

		return $map;
	}

	/**
	 * Compute the Monday of the week for a given date string (Y-m-d)
	 */
	public function getMondayOfWeek($dateYmd)
	{
		$ts     = strtotime($dateYmd);
		$w      = (int) date('w', $ts); // 0=Sunday..6=Saturday
		$offset = ($w + 6) % 7; // days since Monday

		return date('Y-m-d', strtotime("-$offset day", $ts));
	}

	/**
	 * Build 5-week metadata starting from a Monday (backward compatibility)
	 */
	public function buildFiveWeeks(string $mondayStart)
	{
		return $this->buildWeeks($mondayStart, 5);
	}

	/**
	 * Build N-week metadata starting from a Monday
	 * Returns [ 'start_date','end_date','weeks' => [ [ 'start' => Y-m-d, 'dates' => [ [ 'ymd'=>, 'd'=>, 'm'=>, 'y'=> ] x7 ] ] xN ] ]
	 */
	public function buildWeeks(string $mondayStart, int $count)
	{
		// Clamp count between 1 and 12 for safety (supports: previous + current + up to 10 upcoming)
		$count   = max(1, min(12, $count));
		$weeks   = [];
		$startTs = strtotime($mondayStart);
		for ($w = 0; $w < $count; $w++)
		{
			$weekStartTs = strtotime("+{$w} week", $startTs);
			$dates       = [];
			for ($d = 0; $d < 7; $d++)
			{
				$ts      = strtotime("+{$d} day", $weekStartTs);
				$dates[] = [
					'ymd' => date('Y-m-d', $ts),
					'y'   => (int) date('Y', $ts),
					'm'   => (int) date('n', $ts),
					'd'   => (int) date('j', $ts)
				];
			}
			$weeks[] = [
				'start'       => date('Y-m-d', $weekStartTs),
				'dates'       => $dates,
				'week_number' => (int) date('W', $weekStartTs)
			];
		}
		$endTs = strtotime("+" . ($count - 1) . " week +6 day", $startTs);

		return [
			'start_date' => date('Y-m-d', $startTs),
			'end_date'   => date('Y-m-d', $endTs),
			'weeks'      => $weeks
		];
	}





}
