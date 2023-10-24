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
    '3.4.1',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'priority'    => 2,
        'type'        => 'plugin',
        'support'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/issues',
        'details'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository'  => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);
