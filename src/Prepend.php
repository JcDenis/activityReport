<?php
/**
 * @brief activityReport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis and contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use dcCore;
use Dotclear\Core\Process;
use Exception;

/**
 * Prepend process.
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (defined('ACTIVITY_REPORT')) {
            return true;
        }

        try {
            // launch once activity report stuff
            ActivityReport::instance();

            // regirster activity feed URL
            dcCore::app()->url->register(
                My::id(),
                'reports',
                '^reports/((atom|rss2)/(.+))$',
                [UrlHandler::class, 'feed']
            );

            // declare report open
            define('ACTIVITY_REPORT', My::COMPATIBILITY_VERSION);

            // register predefined activities scan
            ActivityBehaviors::register();
        } catch (Exception $e) {
        }

        return true;
    }
}
