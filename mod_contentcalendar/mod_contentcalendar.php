<?php
/*
 *  package: Joomill Content Calendar FREE
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

use Joomill\Module\Contentcalendar\Administrator\Service\BusinessLogicService;
use Joomill\Module\Contentcalendar\Administrator\Service\DataAccessService;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

defined('_JEXEC') or die;

/**
 * Asset Loading Section
 *
 * Register and load required CSS and JavaScript assets using Joomla's WebAssetManager.
 * This ensures proper asset versioning and dependency management.
 */
$app = Factory::getApplication();
$wa  = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('mod_contentcalendar.style', 'mod_contentcalendar/default.css');
$wa->registerAndUseScript('mod_contentcalendar.script', 'mod_contentcalendar/default.js');

/**
 * Service Instantiation Section
 *
 * Create instances of the dedicated service classes for data access and business logic.
 * Since this is a legacy entry point, we manually instantiate services rather than using DI.
 */
$dataAccessService    = new DataAccessService(Factory::getDbo());
$businessLogicService = new BusinessLogicService();

/**
 * Module Parameter Processing
 *
 * Extract and sanitize module configuration parameters. The $params variable
 * is automatically provided by Joomla's module loading system.
 *
 * @var \Joomla\Registry\Registry $params Module parameters from configuration
 */
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx', ''));
$categories      = $params->get('categories', []);

/**
 * Date Processing Section
 *
 * Extract current month and year from request parameters or fall back to current date.
 * Validate the date parameters to ensure they're within acceptable ranges and
 * calculate navigation data for previous/next month links.
 */
$app           = Factory::getApplication();
$input         = $app->getInput();
$current_month = $input->getInt('month', date('n'));
$current_year  = $input->getInt('year', date('Y'));

// Validate month and year using business logic service to ensure safe date ranges
$validated     = $businessLogicService->validateMonthYear($current_month, $current_year);
$current_month = $validated['month'];
$current_year  = $validated['year'];

// Calculate navigation data for calendar month switching using business logic service
$navigation = $businessLogicService->calculateNavigation($current_month, $current_year);
$prev_month = $navigation['prev_month'];
$prev_year  = $navigation['prev_year'];
$next_month = $navigation['next_month'];
$next_year  = $navigation['next_year'];

/**
 * Article Retrieval and Data Organization Section
 *
 * Retrieve articles for the current month using data access service
 * based on selected categories and future article visibility settings.
 * Organize the retrieved articles by day for calendar display using business logic service.
 */


// Monthly default - always use monthly view
$articles        = $dataAccessService->getArticlesForMonth($categories, $current_month, $current_year);
$articles_by_day = $businessLogicService->organizeArticlesByDay($articles);
$calendar_data   = $businessLogicService->prepareCalendarData($current_month, $current_year, $articles_by_day);

/**
 * Template Rendering Section
 *
 * Load and render the module template with the prepared calendar data.
 * Uses Joomla's ModuleHelper to locate the appropriate layout file.
 */
require ModuleHelper::getLayoutPath('mod_contentcalendar', 'default');
