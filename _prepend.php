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
if (!defined('DC_RC_PATH')) {
    return null;
}

Clearbricks::lib()->autoload(['activityReport' => __DIR__ . '/inc/class.activity.report.php']);
Clearbricks::lib()->autoload(['activityReportBehaviors' => __DIR__ . '/inc/class.activity.report.behaviors.php']);

try {
    if (!defined('ACTIVITY_REPORT_V2')) {
        dcCore::app()->__set('activityReport', new activityReport());

        dcCore::app()->url->register(
            'activityReport',
            'reports',
            '^reports/((atom|rss2)/(.+))$',
            ['activityReportPublicUrl', 'feed']
        );

        define('ACTIVITY_REPORT_V2', true);

        activityReportBehaviors::registerBehaviors();
    }
} catch (Exception $e) {
    //throw new Exception('Failed to launch activityReport');
}
