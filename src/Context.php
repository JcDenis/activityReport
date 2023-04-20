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
 * Template helper.
 */
class Context
{
    /**
     * Parse title.
     *
     * @return  string  The parsed title
     */
    public static function parseTitle(): string
    {
        if (!dcCore::app()->ctx || !dcCore::app()->ctx->exists('activityreports')) {
            return '';
        }

        $group  = dcCore::app()->ctx->__get('activityreports')->activity_group;
        $action = dcCore::app()->ctx->__get('activityreports')->activity_action;

        if (!ActivityReport::instance()->groups->get($group)->has($action)) {
            return '';
        }

        return __(ActivityReport::instance()->groups->get($group)->get($action)->title);
    }

    /**
     * Parse content.
     *
     * @return  string  The parsed content
     */
    public static function parseContent(): string
    {
        if (!dcCore::app()->ctx || !dcCore::app()->ctx->exists('activityreports')) {
            return '';
        }

        $group  = dcCore::app()->ctx->__get('activityreports')->activity_group;
        $action = dcCore::app()->ctx->__get('activityreports')->activity_action;
        $logs   = json_decode((string) dcCore::app()->ctx->__get('activityreports')->activity_logs, true);

        if (!is_array($logs) || !ActivityReport::instance()->groups->get($group)->has($action)) {
            return '';
        }

        dcCore::app()->initWikiComment();

        return dcCore::app()->wikiTransform(vsprintf(
            __(ActivityReport::instance()->groups->get($group)->get($action)->message),
            $logs
        ));
    }
}
