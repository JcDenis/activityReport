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

$d = dirname(__FILE__) . '/inc/';
$__autoload['activityReport'] =  $d . 'class.activity.report.php';
$__autoload['activityReportBehaviors'] = $d . 'class.activity.report.behaviors.php';

try {
    if (!defined('ACTIVITY_REPORT')) {
        $core->activityReport = new activityReport($core);

        $core->url->register(
            'activityReport',
            'reports',
            '^reports/((atom|rss2)/(.+))$',
            ['activityReportPublicUrl', 'feed']
        );

        define('ACTIVITY_REPORT', true);

        activityReportBehaviors::registerBehaviors($core);
    }
} catch (Exception $e) {
    //throw new Exception('Failed to launch activityReport');
}