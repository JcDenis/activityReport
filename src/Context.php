<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use dcCore;
use Dotclear\Database\MetaRecord;

/**
 * @brief       activityReport frontend context class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
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
        if (!dcCore::app()->ctx
            || !dcCore::app()->ctx->exists('activityreports')
            || !(dcCore::app()->ctx->__get('activityreports') instanceof MetaRecord)
        ) {
            return '';
        }

        $group  = dcCore::app()->ctx->__get('activityreports')->f('activity_group');
        $action = dcCore::app()->ctx->__get('activityreports')->f('activity_action');

        if (!is_string($group)
            || !is_string($action)
            || !ActivityReport::instance()->groups->get($group)->has($action)
        ) {
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
        if (!dcCore::app()->ctx
            || !dcCore::app()->ctx->exists('activityreports')
            || !(dcCore::app()->ctx->__get('activityreports') instanceof MetaRecord)
        ) {
            return '';
        }

        $group  = dcCore::app()->ctx->__get('activityreports')->f('activity_group');
        $action = dcCore::app()->ctx->__get('activityreports')->f('activity_action');
        $logs   = dcCore::app()->ctx->__get('activityreports')->f('activity_logs');
        $logs   = json_decode(is_string($logs) ? $logs : '', true);

        if (!is_string($group)
            || !is_string($action)
            || !is_array($logs)
            || !ActivityReport::instance()->groups->get($group)->has($action)
        ) {
            return '';
        }

        dcCore::app()->initWikiComment();

        return dcCore::app()->wikiTransform(vsprintf(
            __(ActivityReport::instance()->groups->get($group)->get($action)->message),
            $logs
        ));
    }
}
