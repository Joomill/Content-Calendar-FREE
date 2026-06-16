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

class ProField extends FormField
{
	protected $type = 'pro';

	protected function getInput()
	{
		$text = Text::_('MOD_CONTENTCALENDAR_PRO_ONLY');
		$url  = 'https://www.joomill-extensions.com/extensions/content-planner-calendar-module';

		return '<a class="badge bg-success text-decoration-none" href="' . $url . '" target="_blank" rel="noopener">'
			. $text . '</a>';
	}
}
