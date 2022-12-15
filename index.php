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

if (!defined('ACTIVITY_REPORT_V2')) {
    return null;
}

dcPage::check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_ADMIN,
]));

$super = dcCore::app()->auth->isSuperAdmin() && !empty($_REQUEST['super']);

if ($super) {
    dcCore::app()->activityReport->setGlobal();
}

$logs = dcCore::app()->activityReport->getLogs([]);

if ($super) {
    $breadcrumb = [
        __('Current blog')                                        => dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__), ['super' => 0]),
        '<span class="page-title">' . __('All blogs') . '</span>' => '',
    ];
} else {
    $breadcrumb = ['<span class="page-title">' . __('Current blog') . '</span>' => ''];
    if (dcCore::app()->auth->isSuperAdmin()) {
        $breadcrumb[__('All blogs')] = dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__), ['super' => 1]);
    }
}

echo '<html><head><title>' . __('Activity report') . '</title></head><body>' .
dcPage::breadcrumb(array_merge([__('Activity report') => '', __('Logs') => ''], $breadcrumb), ['hl' => false]) .
dcPage::notices();

if ($logs->isEmpty()) {
    echo '<p>' . __('No log') . '</p>';
} else {
    echo '
    <div class="table-outer"><table><thead>
    <tr>
    <th>' . __('Action') . '</th>
    <th>' . __('Message') . '</th>
    <th>' . __('Date') . '</th>';
    if ($super) {
        echo '<th>' . __('Blog') . '</th>';
    }
    echo '</tr></thead><tbody>';

    while ($logs->fetch()) {
        $action = dcCore::app()->activityReport->getGroups($logs->activity_group, $logs->activity_action);

        if (empty($action)) {
            continue;
        }

        $off  = $super && $logs->activity_blog_status == 1 ? ' offline' : '';
        $date = dt::str(
            dcCore::app()->blog->settings->system->date_format . ', ' . dcCore::app()->blog->settings->system->time_format,
            strtotime($logs->activity_dt),
            dcCore::app()->auth->getInfo('user_tz')
        );
        $msg = vsprintf(__($action['msg']), dcCore::app()->activityReport->decode($logs->activity_logs));

        echo '
        <tr class="line' . $off . '">
        <td class="nowrap">' . __($action['title']) . '</td>
        <td class="maximal">' . $msg . '</td>
        <td class="nowrap">' . $date . '</td>';
        if ($super) {
            echo '<td class="nowrap">' . $logs->blog_id . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
dcCore::app()->activityReport->unsetGlobal();

echo '</body></html>';
