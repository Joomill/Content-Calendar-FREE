<?php
/*
 *  package: Joomill Content Calendar FREE
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Contentcalendar\Administrator\Dispatcher;

use Joomill\Module\Contentcalendar\Administrator\Service\BusinessLogicService;
use Joomill\Module\Contentcalendar\Administrator\Service\DataAccessService;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

/**
 * Dispatcher class for Content Calendar Module
 *
 * Implements the Joomla 4/5/6 module dispatcher pattern. The module dispatcher
 * factory instantiates this class with the standard ($module, $app, $input)
 * signature, so dependencies are resolved inside getLayoutData() rather than
 * through the constructor.
 *
 * @since  1.0.0
 */
class Dispatcher extends AbstractModuleDispatcher
{
	/**
	 * Prepare and return layout data for the calendar template
	 *
	 * Reads the module parameters and the requested month/year, retrieves the
	 * matching articles and organizes them into the calendar grid data consumed
	 * by the template.
	 *
	 * @return  array  Layout data containing moduleclass_sfx, calendar_data and params
	 *
	 * @since   1.0.0
	 */
	protected function getLayoutData()
	{
		$data   = parent::getLayoutData();
		$params = $data['params'];
		$app    = $data['app'];
		$input  = $app->getInput();

		// Instantiate the module services. The database comes from the DI container.
		$dataAccessService    = new DataAccessService(Factory::getContainer()->get(DatabaseInterface::class));
		$businessLogicService = new BusinessLogicService();

		// Module parameters
		$moduleclass_sfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''));
		$categories      = $params->get('categories', []);

		// Requested month/year, falling back to the current date
		$current_month = $input->getInt('month', (int) date('n'));
		$current_year  = $input->getInt('year', (int) date('Y'));

		// Validate to keep date calculations within safe ranges
		$validated     = $businessLogicService->validateMonthYear($current_month, $current_year);
		$current_month = $validated['month'];
		$current_year  = $validated['year'];

		// Retrieve the articles only for users allowed to manage content; the
		// calendar exposes article titles, notes and authors.
		$articles = [];
		$user     = $app->getIdentity();

		if ($user && $user->authorise('core.manage', 'com_content')) {
			$articles = $dataAccessService->getArticlesForMonth($categories, $current_month, $current_year);
		}

		// Organize the articles for the calendar grid
		$articles_by_day = $businessLogicService->organizeArticlesByDay($articles);
		$calendar_data   = $businessLogicService->prepareCalendarData($current_month, $current_year, $articles_by_day);

		$data['moduleclass_sfx'] = $moduleclass_sfx;
		$data['calendar_data']   = $calendar_data;
		$data['params']          = $params;

		return $data;
	}
}
