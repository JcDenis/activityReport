<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use Dotclear\Core\Process;
use Dotclear\Plugin\Uninstaller\Uninstaller;

/**
 * @brief       activityReport uninstall class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Uninstall extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::UNINSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        Uninstaller::instance()
            ->addUserAction(
                'tables',
                'delete',
                My::ACTIVITY_TABLE_NAME
            )
            ->addUserAction(
                'settings',
                'delete_all',
                My::id()
            )
            ->addUserAction(
                'versions',
                'delete',
                My::id()
            )
            ->addUserAction(
                'plugins',
                'delete',
                My::id()
            )
            ->addDirectAction(
                'tables',
                'delete',
                My::ACTIVITY_TABLE_NAME
            )
            ->addDirectAction(
                'settings',
                'delete_all',
                My::id()
            )
            ->addDirectAction(
                'versions',
                'delete',
                My::id()
            )
            ->addDirectAction(
                'plugins',
                'delete',
                My::id()
            )
        ;

        // no custom action
        return false;
    }
}
