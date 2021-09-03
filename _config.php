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

if (!defined('DC_CONTEXT_MODULE')) {
    return null;
}

if (!activityReport::hasMailer()) {

    echo '<p class="error">' . __('This server has no mail function, activityReport not send email report.') . '</p>';
}

activityReportLib::settingTab($core, __('Settings'));

if ($core->auth->isSuperAdmin()) {
    activityReportLib::settingTab($core, __('Super settings'), true);
}