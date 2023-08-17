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
use Dotclear\Module\MyPlugin;

/**
 * This module definitions.
 */
class My extends MyPlugin
{
    /** @var    string  Activity database table name */
    public const ACTIVITY_TABLE_NAME = 'activity';

    /** @var    int     Incremental version by breaking changes */
    public const COMPATIBILITY_VERSION = 3;

    public static function checkCustomContext(int $context): ?bool
    {
        switch($context) {
            case My::FRONTEND:
                return defined('ACTIVITY_REPORT') && My::isInstalled();
            case My::BACKEND:
                return defined('DC_CONTEXT_ADMIN') && defined('ACTIVITY_REPORT') && My::isInstalled();
            case My::CONFIG:
            case My::MANAGE:
                return defined('DC_CONTEXT_ADMIN')
                    && defined('ACTIVITY_REPORT')
                    && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_ADMIN,
                    ]), dcCore::app()->blog?->id);
            default:
                return null;
        }
    }

    /**
     * Check is module is trully installed.
     *
     * Required as table structrue has changed
     */
    public static function isInstalled(): bool
    {
        return dcCore::app()->getVersion(self::id()) == (string) dcCore::app()->plugins->getDefine(self::id())->get('version');
    }
}
