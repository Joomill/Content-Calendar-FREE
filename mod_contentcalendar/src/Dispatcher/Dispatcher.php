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
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

defined('_JEXEC') or die;

/**
 * Modern dispatcher class for Content Calendar Module
 *
 * Implements the modern Joomla 4/5 module dispatcher pattern with dependency injection.
 * Handles module initialization, asset loading, parameter processing, and data preparation
 * for the calendar template. Uses helper factory pattern for clean separation of concerns.
 *
 * @since  1.0.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
	use HelperFactoryAwareTrait;

	/**
	 * Data access service
	 *
	 * @var    DataAccessService
	 * @since  1.0.0
	 */
	private $dataAccessService;

	/**
	 * Business logic service
	 *
	 * @var    BusinessLogicService
	 * @since  1.0.0
	 */
	private $businessLogicService;


	/**
	 * Constructor
	 *
	 * @param   DataAccessService     $dataAccessService     Data access service
	 * @param   BusinessLogicService  $businessLogicService  Business logic service
	 *
	 * @since   1.0.0
	 */
	public function __construct(DataAccessService $dataAccessService, BusinessLogicService $businessLogicService)
	{
		$this->dataAccessService    = $dataAccessService;
		$this->businessLogicService = $businessLogicService;
	}

	/**
	 * Prepare and return layout data for the calendar template
	 *
	 * Orchestrates the entire data preparation process for the calendar module.
	 * Loads required CSS/JS assets, processes module parameters, validates date inputs,
	 * retrieves articles from the database, and organizes all data for template rendering.
	 * Integrates with the helper factory pattern for clean dependency management.
	 *
	 * @return  array  Associative array containing moduleclass_sfx, calendar_data, and params
	 *
	 * @throws  Exception  When helper factory operations or database queries fail
	 * @since   1.0.0
	 *
	 */
	protected function getLayoutData()
	{
		$data = parent::getLayoutData();

		// Get module parameters
		$moduleclass_sfx = htmlspecialchars($this->getParams()->get('moduleclass_sfx', ''));
		$categories      = $this->getParams()->get('categories', []);


		// Get current month and year from request or use current date
		$app           = Factory::getApplication();
		$input         = $app->getInput();
		$current_month = $input->getInt('month', date('n'));
		$current_year  = $input->getInt('year', date('Y'));


        // Default monthly view
        // Validate month and year
        $validated     = $this->businessLogicService->validateMonthYear($current_month, $current_year);
        $current_month = $validated['month'];
        $current_year  = $validated['year'];

        // Calculate navigation using business logic service
        $navigation = $this->businessLogicService->calculateNavigation($current_month, $current_year);
        $prev_month = $navigation['prev_month'];
        $prev_year  = $navigation['prev_year'];
        $next_month = $navigation['next_month'];
        $next_year  = $navigation['next_year'];

        // Get articles for the current month using data access service
        $articles = $this->dataAccessService->getArticlesForMonth($categories, $current_month, $current_year);

        // Organize articles by day using business logic service
        $articles_by_day = $this->businessLogicService->organizeArticlesByDay($articles);

        // Prepare calendar data using business logic service
        $calendar_data = $this->businessLogicService->prepareCalendarData(
            $current_month,
            $current_year,
            $articles_by_day
        );

        $data['moduleclass_sfx'] = $moduleclass_sfx;
        $data['calendar_data']   = $calendar_data;
        $data['params']          = $this->getParams();

        return $data;
	}

}
