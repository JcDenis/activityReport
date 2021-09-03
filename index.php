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

if (!defined('ACTIVITY_REPORT')) {
    return null;
}

dcPage::check('admin');

$report =& $core->activityReport;
$super = $core->auth->isSuperAdmin() && !empty($_REQUEST['super']);

if ($super) {
    $report->setGlobal();
}

$logs = $report->getLogs([]);

if ($super) {
    $breadcrumb = [
         __('Current blog') => $core->adminurl->get('admin.plugin.activityReport', ['super' => 0]),
         '<span class="page-title">' . __('All blogs') . '</span>' => ''
    ];
} else {
    $breadcrumb = ['<span class="page-title">' . __('Current blog') . '</span>' => ''];
    if ($core->auth->isSuperAdmin()) {
        $breadcrumb[__('All blogs')] = $core->adminurl->get('admin.plugin.activityReport', ['super' => 1]);
    }
}

echo '<html><head><title>' . __('Activity report') . '</title></head><body>' .
dcPage::breadcrumb(array_merge([__('Activity report') => '', __('Logs') => ''], $breadcrumb),['hl' => false]) .
dcPage::notices();

if ($logs->isEmpty()) {
        echo '<p>'.__('No log').'</p>';
} else {
    echo '
    <div class="table-outer"><table><thead>
    <tr>
    <th>' . __('Action') . '</th>
    <th>' . __('Message') . '</th>
    <th>' . __('Date') . '</th>';
    if ($super) {
        echo '<th>' . __('Blog') .'</th>';
    }
    echo '</tr></thead><tbody>';

    while($logs->fetch()) {
        $action = $report->getGroups($logs->activity_group, $logs->activity_action);

        if (empty($action)) {
            continue;
        }

        $off = $super && $logs->activity_blog_status == 1 ? ' offline' : '';
        $date = dt::str(
            $core->blog->settings->system->date_format . ', ' . $core->blog->settings->system->time_format,
            strtotime($logs->activity_dt),
            $core->auth->getInfo('user_tz')
        );
        $msg = vsprintf(__($action['msg']), $report->decode($logs->activity_logs));

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
$report->unsetGlobal();

echo '</body></html>';