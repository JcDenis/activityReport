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

$this->registerModule(
    'Activity report',
    'Receive your blog activity by email, feed, or on dashboard',
    'Jean-Christian Denis and contributors',
    '1.1',
    [
        'requires' => [['core', '2.19']],
        'permissions' => 'admin',
        'priority' => -1000000,
        'type' => 'plugin',
        'support' => 'https://github.com/JcDenis/activityReport',
        'details' => 'http://plugins.dotaddict.org/dc2/details/activityReport',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/activityReport/master/dcstore.xml'
    ]
);