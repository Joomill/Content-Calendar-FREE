<?php
/*
 *  package: Joomill Content Calendar FREE
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class Mod_ContentcalendarInstallerScript
{
	public function install($parent)
	{
		// Enable the extension
		$this->enableModule();

		return true;
	}

	private function enableModule()
	{
		// Check if Module has not been published yet
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__modules'));
		$query->where($db->quoteName('module') . ' = ' . $db->quote('mod_contentcalendar'));
		$query->where($db->quoteName('published') . ' = 1');
		$query->where($db->quoteName('position') . ' = ' . $db->quote('icon'));
		$db->setQuery($query);
		$moduleId = $db->loadResult();

		// If the Module has not been published, publish + assign it

		if (empty($moduleId))
		{
			// Change Module settings to auto publish it on position cpanel
			$query      = $db->getQuery(true);
			$fields     = array(
				$db->quoteName('title') . ' = ' . $db->quote('Content Calendar'),
				$db->quoteName('published') . ' = 1',
				$db->quoteName('position') . ' = ' . $db->quote('icon'),
				$db->quoteName('access') . ' = 3',
				$db->quoteName('params') . ' = ' .
				$db->quote('{"moduleclass_sfx":"","cache":"0","module_tag":"div",' .
					'"bootstrap_size":"0","header_tag":"h2","header_class":"","style":"0","header_icon":"fa-solid fa-calendar"}'),
			);
			$conditions = array($db->quoteName('module') . ' = ' . $db->quote('mod_contentcalendar'));
			$query->update($db->quoteName('#__modules'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$db->execute();

			// Get ID for module
			$query = $db->getQuery(true);
			$query->select($db->quoteName('id'));
			$query->from($db->quoteName('#__modules'));
			$query->where($db->quoteName('module') . ' = ' . $db->quote('mod_contentcalendar'));
			$db->setQuery($query);
			$moduleId = $db->loadResult();

			// Add to modules_menu
			$query  = $db->getQuery(true);
			$fields = array(
				$db->quoteName('moduleid') . ' = ' . $db->quote($moduleId),
				$db->quoteName('menuid') . ' = 0',
			);

			$query->insert($db->quoteName('#__modules_menu'))->set($fields);
			$db->setQuery($query);
			$db->execute();
		}
	}
}
