<?php
/**
 * @file
 * @brief       The plugin activityReport definition
 * @ingroup     activityReport
 *
 * @defgroup    activityReport Plugin activityReport.
 *
 * Log and receive your blog activity by email, feed, or on dashboard.
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Activity log',
    'Log and receive your blog activity by email, feed, or on dashboard',
    'Jean-Christian Denis and contributors',
    '3.4.2',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'priority'    => 2,
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-02-24T23:31:12+00:00',
    ]
);
