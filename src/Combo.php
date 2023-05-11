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

/**
 * Combo helper.
 */
class Combo
{
    /**
     * Mail content formats.
     *
     * @return  array<string,string>    The formats combo
     */
    public static function mailformat(): array
    {
        $combo = [];
        foreach (ActivityReport::instance()->formats->dump() as $format) {
            $combo[$format->name] = $format->id;
        }

        return $combo;
    }

    /**
     * Get report intervals.
     *
     * @return  array<string,int>   The intervals combo
     */
    public static function interval(): array
    {
        return [
            __('every hour')     => 3600,
            __('every 2 hours')  => 7200,
            __('2 times by day') => 43200,
            __('every day')      => 86400,
            __('every 2 days')   => 172800,
            __('every week')     => 604800,
        ];
    }

    /**
     * Get obsolete period.
     *
     * @return  array<string,int>   The obsolete period combo
     */
    public static function obselete(): array
    {
        return [
            __('every hour')     => 3600,
            __('every 2 hours')  => 7200,
            __('2 times by day') => 43200,
            __('every day')      => 86400,
            __('every 2 days')   => 172800,
            __('every week')     => 604800,
            __('every 2 weeks')  => 1209600,
            __('every 4 weeks')  => 2419200,
        ];
    }
}
