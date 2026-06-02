<?php
/*
 *  package: Joomill Content Calendar FREE
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

use Joomill\Module\Contentcalendar\Administrator\Helper\ContentCalendarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

// Get the current URI for navigation
$current_uri = Uri::getInstance();
$base_url    = $current_uri->toString(['scheme', 'host', 'port', 'path', 'query']);

// Remove existing month/year parameters
$base_url = preg_replace('/[&?]month=\d+/', '', $base_url);
$base_url = preg_replace('/[&?]year=\d+/', '', $base_url);

// Add proper separator
$separator = (strpos($base_url, '?') !== false) ? '&' : '?';

// Build return parameter to come back to this dashboard URL after editing
$return_param = base64_encode(Uri::getInstance()->toString());
?>

<div class="mod-contentcalendar default-view <?php
echo $moduleclass_sfx; ?>">
    <!-- Calendar Navigation -->
    <div class="calendar-navigation">
        <a href="<?php
        echo htmlspecialchars(
            $base_url . $separator . 'month=' . (int) $calendar_data['prev_month'] . '&year=' . (int) $calendar_data['prev_year'],
            ENT_QUOTES
        ); ?>"
           class="btn btn-outline-secondary btn-sm prev-month">
            &laquo; <?php
            echo Text::_('MOD_CONTENTCALENDAR_PREVIOUS_MONTH'); ?>
        </a>

        <h3 class="calendar-title">
            <?php
            // Localize month name using Joomla language files
            $monthKeys      = [
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
                    12 => 'DECEMBER',
            ];
            $cm             = (int) ($calendar_data['current_month'] ?? 0);
            $localizedMonth = '';
            if ($cm >= 1 && $cm <= 12)
            {
                $key        = $monthKeys[$cm];
                $translated = Text::_($key);
                // If translation missing, Text::_ returns the key itself; fallback to original month_name or PHP date
                if ($translated === $key)
                {
                    $translated = $calendar_data['month_name'] ?? date(
                            'M',
                            mktime(0, 0, 0, $cm, 1, (int) ($calendar_data['current_year'] ?? date('Y')))
                    );
                }
                $localizedMonth = $translated;
            }
            else
            {
                $localizedMonth = (string) ($calendar_data['month_name'] ?? '');
            }
            echo htmlspecialchars($localizedMonth) . ' ' . (int) $calendar_data['current_year'];
            ?>
        </h3>

        <a href="<?php
        echo htmlspecialchars(
            $base_url . $separator . 'month=' . (int) $calendar_data['next_month'] . '&year=' . (int) $calendar_data['next_year'],
            ENT_QUOTES
        ); ?>"
           class="btn btn-outline-secondary btn-sm next-month">
            <?php
            echo Text::_('MOD_CONTENTCALENDAR_NEXT_MONTH'); ?> &raquo;
        </a>
    </div>

    <!-- Calendar Grid -->
    <div class="calendar-grid">
        <table class="table calendar-table">
            <thead>
            <tr>
                <th><?php
                    echo Text::_('MON'); ?></th>
                <th><?php
                    echo Text::_('TUE'); ?></th>
                <th><?php
                    echo Text::_('WED'); ?></th>
                <th><?php
                    echo Text::_('THU'); ?></th>
                <th><?php
                    echo Text::_('FRI'); ?></th>
                <th><?php
                    echo Text::_('SAT'); ?></th>
                <th><?php
                    echo Text::_('SUN'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            $day            = 1;
            $days_in_month  = $calendar_data['days_in_month'];
            $first_day      = $calendar_data['first_day_of_week'];
            $current_date   = date('Y-m-d');
            $calendar_month = sprintf('%04d-%02d', $calendar_data['current_year'], $calendar_data['current_month']);

            // Calculate weeks needed
            $weeks_needed = ceil(($days_in_month + $first_day) / 7);

            for ($week = 0; $week < $weeks_needed; $week++): ?>
                <tr>
                    <?php
                    for ($day_of_week = 0; $day_of_week < 7; $day_of_week++):
                        $cell_day = ($week * 7) + $day_of_week - $first_day + 1;
                        ?>
                        <td class="calendar-day<?php
                        if ($cell_day < 1 || $cell_day > $days_in_month)
                        {
                            echo ' empty-day';
                        }
                        if ($cell_day == date('j') && $calendar_month == date('Y-m'))
                        {
                            echo ' today';
                        }
                        if (isset($calendar_data['articles_by_day'][$cell_day]))
                        {
                            echo ' has-articles';
                        }
                        ?>"
                            data-day="<?php
                            echo $cell_day; ?>"
                            data-date="<?php
                            echo sprintf(
                                    '%04d-%02d-%02d',
                                    $calendar_data['current_year'],
                                    $calendar_data['current_month'],
                                    $cell_day
                            ); ?>">

                            <?php
                            if ($cell_day >= 1 && $cell_day <= $days_in_month): ?>
                                <div class="day-number"><?php
                                    echo $cell_day; ?>
                                </div>
                                <?php
                                if (isset($calendar_data['articles_by_day'][$cell_day])): ?>
                                    <div class="articles-list">
                                        <?php
                                        foreach ($calendar_data['articles_by_day'][$cell_day] as $article):
                                            $edit_url = Route::_(
                                                    'index.php?option=com_content&task=article.edit&id=' . $article->id . '&return=' . $return_param
                                            );
                                            $is_future = strtotime($article->publish_up) > time();
                                            $publish_time = date('H:i', strtotime($article->publish_up));
                                            $publish_date = date('Y-m-d', strtotime($article->publish_up));
                                            $author_name = !empty($article->author_name) ? $article->author_name : Text::_(
                                                    'MOD_CONTENTCALENDAR_UNKNOWN_AUTHOR'
                                            );
                                            $item_color = ContentCalendarHelper::getItemColorSimple($article);

                                            // Build detailed tooltip text (multiline)
                                            $tooltip_parts   = [];
                                            $tooltip_parts[] = Text::_(
                                                            'MOD_CONTENTCALENDAR_TOOLTIP_TITLE'
                                                    ) . ': ' . (string) $article->title;
                                            $tooltip_parts[] = Text::_(
                                                            'MOD_CONTENTCALENDAR_TOOLTIP_DATE'
                                                    ) . ': ' . $publish_date;
                                            $tooltip_parts[] = Text::_(
                                                            'MOD_CONTENTCALENDAR_TOOLTIP_TIME'
                                                    ) . ': ' . $publish_time;
                                            $tooltip_parts[] = Text::_(
                                                            'MOD_CONTENTCALENDAR_TOOLTIP_AUTHOR'
                                                    ) . ': ' . $author_name;
                                            if (!empty($article->category_title))
                                            {
                                                $tooltip_parts[] = Text::_(
                                                                'MOD_CONTENTCALENDAR_TOOLTIP_CATEGORY'
                                                        ) . ': ' . $article->category_title;
                                            }
                                            if (!empty($article->tag_names))
                                            {
                                                $tooltip_parts[] = Text::_(
                                                                'MOD_CONTENTCALENDAR_TOOLTIP_TAGS'
                                                        ) . ': ' . $article->tag_names;
                                            }
                                            if (!empty($article->note))
                                            {
                                                $tooltip_parts[] = Text::_(
                                                                'MOD_CONTENTCALENDAR_TOOLTIP_NOTE'
                                                        ) . ': ' . $article->note;
                                            }
                                            $tooltip_parts[] = Text::_(
                                                            'MOD_CONTENTCALENDAR_TOOLTIP_ID'
                                                    ) . ': ' . $article->id;
                                            $status_text     = $is_future ? Text::_(
                                                    'MOD_CONTENTCALENDAR_STATUS_FUTURE'
                                            ) : Text::_('MOD_CONTENTCALENDAR_STATUS_PUBLISHED');
                                            $tooltip_parts[] = Text::_(
                                                            'MOD_CONTENTCALENDAR_TOOLTIP_STATUS'
                                                    ) . ': ' . $status_text;
                                            $tooltip_text    = implode("\n", array_map('strval', $tooltip_parts));
                                            ?>
                                            <a class="article-bar<?php
                                            echo $is_future ? ' future-article' : ' published-article'; ?><?php
                                            ?>"
                                               href="<?php
                                               echo $edit_url; ?>"
                                               style="background-color: <?php
                                               echo htmlspecialchars($item_color); ?>;"
                                               data-article-id="<?php
                                               echo $article->id; ?>"
                                               data-is-future="<?php
                                               echo $is_future ? '1' : '0'; ?>"
                                               data-original-date="<?php
                                               echo $publish_date; ?>"
                                               data-title="<?php
                                               echo htmlspecialchars($article->title); ?>"
                                               data-author="<?php
                                               echo htmlspecialchars($author_name); ?>"
                                               data-category="<?php
                                               echo htmlspecialchars($article->category_title ?? ''); ?>"
                                               data-tags="<?php
                                               echo htmlspecialchars($article->tag_names ?? ''); ?>"
                                               data-note="<?php
                                               echo htmlspecialchars($article->note ?? ''); ?>"
                                               data-time="<?php
                                               echo $publish_time; ?>"
                                               title="<?php
                                               echo htmlspecialchars($tooltip_text, ENT_QUOTES); ?>">
                                                <?php
                                                echo htmlspecialchars($article->title); ?>
                                            </a>
                                        <?php
                                        endforeach; ?>
                                    </div>
                                <?php
                                endif; ?>
                            <?php
                            endif; ?>
                        </td>
                    <?php
                    endfor; ?>
                </tr>
            <?php
            endfor; ?>
            </tbody>
        </table>
    </div>
</div>
