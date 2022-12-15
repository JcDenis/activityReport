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

class initActivityReport
{
    public const ACTIVITY_TABLE_NAME = 'activity';
    public const SETTING_TABLE_NAME  = 'activity_setting';
    public const CACHE_DIR_NAME      = 'activityreport';
}
