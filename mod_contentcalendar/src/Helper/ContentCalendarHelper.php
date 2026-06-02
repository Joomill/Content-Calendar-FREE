<?php
/*
 *  package: Joomill Content Calendar FREE
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Contentcalendar\Administrator\Helper;

use DateTime;
use Exception;
use Joomill\Module\Contentcalendar\Administrator\Service\DataAccessService;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

defined('_JEXEC') or die;

/**
 * Helper class for Content Calendar Module
 *
 * This class provides static utility methods for the Content Calendar Module,
 * serving as a bridge between the legacy helper pattern and the modern service
 * architecture. It handles color mapping operations and AJAX functionality
 * for drag-and-drop calendar operations.
 *
 * @since  1.0.0
 */
class ContentCalendarHelper
{


	/**
	 * Simplified color getter: always uses category-based deterministic color without any params.
	 *
	 * @param object $article Article object containing catid
	 * @return string Hex color code
	 */
	public static function getItemColorSimple($article): string
	{
		return '#1a73e8';
	}
}
