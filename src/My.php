<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief       activityReport My helper.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    /**
     * Activity database table name.
     *
     * @var     string   ACTIVITY_TABLE_NAME
     */
    public const ACTIVITY_TABLE_NAME = 'activity';

    /**
     * Incremental version by breaking changes.
     *
     * @var     int     COMPATIBILITY_VERSION
     */
    public const COMPATIBILITY_VERSION = 3;

    public static function checkCustomContext(int $context): ?bool
    {
        return match ($context) {
            My::FRONTEND =>
                defined('ACTIVITY_REPORT') && My::isInstalled(),
            My::BACKEND =>
                App::task()->checkContext('BACKEND') && defined('ACTIVITY_REPORT') && My::isInstalled(),
            My::CONFIG, My::MANAGE => 
                App::task()->checkContext('BACKEND')
                    && defined('ACTIVITY_REPORT')
                    && App::auth()->check(App::auth()->makePermissions([
                        App::auth()::PERMISSION_ADMIN,
                    ]), App::blog()->id()),

            default => 
                null,
        };
    }

    /**
     * Check is module is trully installed.
     *
     * Required as table structrue has changed
     */
    public static function isInstalled(): bool
    {
        return App::version()->getVersion(self::id()) == (string) App::plugins()->getDefine(self::id())->get('version');
    }
}
