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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

$this->addUserAction(
    /* type */
    'tables',
    /* action */
    'delete',
    /* ns */
    initActivityReport::ACTIVITY_TABLE_NAME,
    /* description */
    sprintf(__('delete %s table'), '"activity"')
);

$this->addUserAction(
    /* type */
    'tables',
    /* action */
    'delete',
    /* ns */
    initActivityReport::SETTING_TABLE_NAME,
    /* description */
    sprintf(__('delete %s table'), '"activity_setting"')
);

$this->addUserAction(
    /* type */
    'plugins',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* description */
    __('delete plugin files')
);

$this->addUserAction(
    /* type */
    'versions',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* description */
    __('delete the version number')
);

$this->addDirectAction(
    /* type */
    'versions',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* description */
    sprintf(__('delete %s version number'), basename(__DIR__))
);

$this->addDirectAction(
    /* type */
    'plugins',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* description */
    sprintf(__('delete %s plugin files'), basename(__DIR__))
);
