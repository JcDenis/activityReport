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

/**
 * This module definitions.
 */
class My
{
    /** @var    string  Activity database table name */
    public const ACTIVITY_TABLE_NAME = 'activity';

    /** @var    string  Cache sub directory name */
    public const CACHE_DIR_NAME = 'activityreport';

    /** @var    int     Incremental version by breaking changes */
    public const COMPATIBILITY_VERSION = 3;

    /** @var    string  Required php version */
    public const PHP_MIN = '7.4';

    /**
     * This module id.
     */
    public static function id(): string
    {
        return basename(dirname(__DIR__));
    }

    /**
     * This module name.
     */
    public static function name(): string
    {
        $name = dcCore::app()->plugins->moduleInfo(self::id(), 'name');

        return __(is_string($name) ? $name : self::id());
    }

    /**
     * This module path.
     */
    public static function path(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Check this module PHP version compliant.
     */
    public static function phpCompliant(): bool
    {
        return version_compare(phpversion(), self::PHP_MIN, '>=');
    }

    /**
     * Check is module is trully installed.
     *
     * Required as table structrue has changed
     */
    public static function isInstalled(): bool
    {
        return dcCore::app()->getVersion(self::id()) == dcCore::app()->plugins->moduleInfo(self::id(), 'version');
    }
}
