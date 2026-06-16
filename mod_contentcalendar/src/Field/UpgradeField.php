<?php
/*
 *  package: Joomill Content Calendar FREE
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Contentcalendar\Administrator\Field;

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

class UpgradeField extends FormField
{
	protected $type = 'upgrade';

	protected function getInput()
	{
		$text = Text::_('MOD_CONTENTCALENDAR_FREE_VERSION');
		$url  = 'https://www.joomill-extensions.com/extensions/content-planner-calendar-module';

		return '<div class="alert alert-success">' . $text
			. ' <a class="alert-link" href="' . $url . '" target="_blank" rel="noopener">Content Calendar PRO</a></div>';
	}
}
