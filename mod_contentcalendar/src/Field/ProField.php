<?php

/**
 * Content Calendar
 *
 * @copyright   Copyright (c) 2026 Jeroen Moolenschot | Joomill
 * @license     GNU General Public License version 3 or later; see LICENSE
 * @link        https://www.joomill-extensions.com
 */

namespace Joomill\Module\Contentcalendar\Administrator\Field;

// No direct access.
// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

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
