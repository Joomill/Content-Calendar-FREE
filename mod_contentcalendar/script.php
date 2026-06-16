<?php
/*
 *  package: Joomill Content Calendar FREE
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

/**
 * Installation script class for Content Calendar module
 *
 * @since  1.0.0
 */
class mod_contentcalendarInstallerScript implements InstallerScriptInterface
{
    /**
     * Minimum Joomla version to check
     *
     * @var    string
     * @since  1.0.0
     */
    private $minimumJoomlaVersion = '5.0';

    /**
     * Minimum PHP version to check
     *
     * @var    string
     * @since  1.0.0
     */
    private $minimumPHPVersion = JOOMLA_MINIMUM_PHP;

    /**
     * Function called after the extension is installed
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Function called after the extension is updated
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Function called after the extension is uninstalled
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Function called before extension installation/update/removal procedure commences
     *
     * @param   string            $type    The type of change (install, update, discover_install or uninstall)
     * @param   InstallerAdapter  $parent  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function preflight(string $type, InstallerAdapter $parent): bool
    {
        try {
            if ($type !== 'uninstall') {
                // Check for the minimum PHP version before continuing
                if (!empty($this->minimumPHPVersion) && version_compare(PHP_VERSION, $this->minimumPHPVersion, '<')) {
                    Log::add(
                        Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPHPVersion),
                        Log::WARNING,
                        'jerror'
                    );
                    return false;
                }
                // Check for the minimum Joomla version before continuing
                if (!empty($this->minimumJoomlaVersion) && version_compare(JVERSION, $this->minimumJoomlaVersion, '<')) {
                    Log::add(
                        Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomlaVersion),
                        Log::WARNING,
                        'jerror'
                    );
                    return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::add('Error during preflight check: ' . $e->getMessage(), Log::ERROR, 'mod_contentcalendar');
            return false;
        }
    }

    /**
     * Function called after extension installation/update/removal procedure commences
     *
     * @param   string            $type    The type of change (install, update, discover_install or uninstall)
     * @param   InstallerAdapter  $parent  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function postflight(string $type, InstallerAdapter $parent): bool
    {
        try {
            $this->loadInstallLanguage();

            if ($type === 'install') {
                $this->enableModule();
                $this->printInstallMessage();
            }

            if ($type === 'uninstall') {
                $this->printUninstallMessage();
            }

            return true;
        } catch (\Exception $e) {
            Log::add('Error during postflight: ' . $e->getMessage(), Log::ERROR, 'mod_contentcalendar');
            // Still return true to not block the installation/uninstallation process
            // The error is logged but we don't want to prevent the process from completing
            return true;
        }
    }

    /**
     * Auto-publish the module to the administrator icon position on first install
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function enableModule(): void
    {
        // Check if Module has not been published yet
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($db->quoteName('id'));
        $query->from($db->quoteName('#__modules'));
        $query->where($db->quoteName('module') . ' = ' . $db->quote('mod_contentcalendar'));
        $query->where($db->quoteName('published') . ' = 1');
        $query->where($db->quoteName('position') . ' = ' . $db->quote('icon'));
        $db->setQuery($query);
        $moduleId = $db->loadResult();

        // If the Module has not been published, publish + assign it
        if (empty($moduleId)) {
            // Change Module settings to auto publish it on position icon
            $query  = $db->getQuery(true);
            $fields = array(
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

    /**
     * Make the module install strings available to the script
     *
     * The installer normally auto-loads the .sys.ini; this is a safety net so the
     * install and uninstall screens never show raw language keys.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function loadInstallLanguage(): void
    {
        $language = Factory::getApplication()->getLanguage();
        $language->load('mod_contentcalendar.sys', JPATH_ADMINISTRATOR)
            || $language->load('mod_contentcalendar.sys', JPATH_ADMINISTRATOR . '/modules/mod_contentcalendar');
    }

    /**
     * Render the Joomill thank-you and quickstart screen after installation
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function printInstallMessage(): void
    {
        echo '<style>a[target="_blank"]::before {display: none;}</style>';
        echo '<div class="mb-3 text-center"><img src="https://www.joomill-extensions.com/images/joomill-logo.png" alt="Joomill Extensions" /></div>';
        echo '<div class="mb-3 text-center">' . Text::_('MOD_CONTENTCALENDAR_INSTALL_THANKYOU') . '</div>';
        echo '<br>';
        echo '<h3>' . Text::_('MOD_CONTENTCALENDAR_INSTALL_QUICKSTART') . ':</h3>';
        echo '<ul>';
        echo '<li><a style="text-decoration: underline;" href="index.php?option=com_modules&view=modules&client_id=1" target="_blank">' . Text::_('MOD_CONTENTCALENDAR_INSTALL_CONFIGURATION') . '</a></li>';
        echo '<li><a style="text-decoration: underline;" href="https://www.joomill-extensions.com/documentation/content-calendar" target="_blank">' . Text::_('MOD_CONTENTCALENDAR_INSTALL_NEEDHELP') . '</a></li>';
        echo '</ul>';
        echo '<hr>';
        echo '<div class="text-center">' . Text::_('MOD_CONTENTCALENDAR_INSTALL_FOLLOWME') . ':</div>';
        echo $this->socialIcons();
    }

    /**
     * Render the Joomill thank-you screen after uninstallation
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function printUninstallMessage(): void
    {
        echo '<style>a[target="_blank"]::before {display: none;}</style>';
        echo '<div class="mb-3 text-center"><img src="https://www.joomill-extensions.com/images/joomill-logo.png" alt="Joomill Extensions" /></div>';
        echo '<br>';
        echo '<h3 class="text-center">' . Text::_('MOD_CONTENTCALENDAR_UNINSTALL_THANKYOU') . '</h3>';
        echo '<br>';
        echo '<div class="text-center">' . Text::_('MOD_CONTENTCALENDAR_INSTALL_FOLLOWME') . ':</div>';
        echo $this->socialIcons();
    }

    /**
     * Render the Joomill social media follow links
     *
     * @return  string  The social links HTML
     *
     * @since   1.0.0
     */
    private function socialIcons(): string
    {
        return '<div class="text-center">'
            . '<a class="m-2" href="https://www.linkedin.com/in/jeroenmoolenschot/" target="_blank"><i class="fa-brands fa-linkedin"> </i></a>'
            . '<a class="m-2" href="https://www.facebook.com/Joomill" target="_blank"><i class="fa-brands fa-facebook-f"> </i></a>'
            . '<a class="m-2" href="https://www.instagram.com/Joomill" target="_blank"><i class="fa-brands fa-instagram"> </i></a>'
            . '<a class="m-2" href="https://bsky.app/profile/joomill.bsky.social" target="_blank"><i class="fa-brands fa-bluesky"> </i></a>'
            . '<a class="m-2" href="https://joomla.social/@joomill" target="_blank"><i class="fa-brands fa-mastodon"> </i></a>'
            . '<a class="m-2" href="https://www.threads.net/@joomill" target="_blank"><i class="fa-brands fa-threads"> </i></a>'
            . '<a class="m-2" href="https://www.twitter.com/Joomill" target="_blank"><i class="fa-brands fa-x-twitter"> </i></a>'
            . '<a class="m-2" href="https://community.joomla.org/service-providers-directory/listings/67:joomill.html" target="_blank"><i class="fa-brands fa-joomla"> </i></a>'
            . '</div>';
    }
}

return new mod_contentcalendarInstallerScript();
