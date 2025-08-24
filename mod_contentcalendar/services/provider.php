<?php

/*
 *  package: Content Calendar FREE
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace services;

use Joomill\Module\Contentcalendar\Administrator\Service\BusinessLogicService;
use Joomill\Module\Contentcalendar\Administrator\Service\DataAccessService;
use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\HelperFactoryInterface;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Anonymous service provider class for Content Calendar Module
 *
 * Implements ServiceProviderInterface to register module-specific services
 * with Joomla's dependency injection container. This pattern allows for
 * clean separation of concerns and proper dependency management.
 *
 * @since  2.0.0
 */
return new class () implements ServiceProviderInterface {
	/**
	 * Register module services with the dependency injection container
	 *
	 * Registers both the ModuleDispatcherFactory and HelperFactory services
	 * with their respective namespace configurations. This enables the modern
	 * Joomla dispatcher pattern with proper dependency injection.
	 *
	 * @param   Container  $container  The DI container instance to register services with
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	public function register(Container $container)
	{
		$container->registerServiceProvider(new ModuleDispatcherFactory('\\Joomill\\Module\\Contentcalendar'));
		$container->registerServiceProvider(
			new HelperFactory('\\Joomill\\Module\\Contentcalendar\\Administrator\\Helper')
		);

		// Register dedicated service classes
		$container->set(DataAccessService::class, function (Container $container) {
			return new DataAccessService(Factory::getDbo());
		});

		$container->set(BusinessLogicService::class, function (Container $container) {
			return new BusinessLogicService();
		});
	}
};