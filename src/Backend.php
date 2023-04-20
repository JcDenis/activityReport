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

dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    __('Activity report'),
    dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__)),
    dcPage::getPF(basename(__DIR__) . '/icon.png'),
    preg_match(
        '/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__))) . '(&.*)?$/',
        $_SERVER['REQUEST_URI']
    ),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_ADMIN,
    ]), dcCore::app()->blog->id)
);

if (dcCore::app()->activityReport->getSetting('active')) {
    dcCore::app()->addBehavior('adminDashboardContentsV2', ['activityReportAdmin', 'adminDashboardContentsV2']);
    dcCore::app()->addBehavior('adminDashboardOptionsFormV2', ['activityReportAdmin', 'adminDashboardOptionsFormV2']);
    dcCore::app()->addBehavior('adminAfterDashboardOptionsUpdate', ['activityReportAdmin', 'adminAfterDashboardOptionsUpdate']);
}

class activityReportAdmin
{
    public static function adminDashboardContentsV2($items)
    {
        dcCore::app()->auth->user_prefs->addWorkspace(basename(__DIR__));
        $limit = abs((int) dcCore::app()->auth->user_prefs->__get(basename(__DIR__))->dashboard_item);
        if (!$limit) {
            return null;
        }
        $p = [
            'limit' => $limit,
            'order' => 'activity_dt DESC',
            'sql'   => dcCore::app()->activityReport->requests2params(dcCore::app()->activityReport->getSetting('requests')),
        ];
        $lines = [];
        $rs    = dcCore::app()->activityReport->getLogs($p);
        if ($rs->isEmpty()) {
            return null;
        }
        $groups = dcCore::app()->activityReport->getGroups();
        while ($rs->fetch()) {
            $group = $rs->activity_group;

            if (!isset($groups[$group])) {
                continue;
            }
            $lines[] = '<dt title="' . __($groups[$group]['title']) . '">' .
            '<strong>' . __($groups[$group]['actions'][$rs->activity_action]['title']) . '</strong>' .
            '<br />' . dt::str(
                dcCore::app()->blog->settings->system->date_format . ', ' . dcCore::app()->blog->settings->system->time_format,
                strtotime($rs->activity_dt),
                dcCore::app()->auth->getInfo('user_tz')
            ) . '<dt>' .
            '<dd><p>' .
            '<em>' . vsprintf(
                __($groups[$group]['actions'][$rs->activity_action]['msg']),
                dcCore::app()->activityReport->decode($rs->activity_logs)
            ) . '</em></p></dd>';
        }
        if (empty($lines)) {
            return null;
        }
        $items[] = new ArrayObject([
            '<div id="activity-report-logs" class="box medium">' .
            '<h3>' . __('Activity report') . '</h3>' .
            '<dl id="reports">' . implode('', $lines) . '</dl>' .
            '<p class="modules"><a class="module-details" href="' .
            dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__)) . '">' .
            __('View all logs') . '</a> - <a class="module-config" href="' .
            dcCore::app()->adminurl->get('admin.plugins', [
                'module' => basename(__DIR__),
                'conf'   => 1,
                'redir'  => dcCore::app()->adminurl->get('admin.home') . '#activity-report-logs',
            ]) . '">' .
            __('Configure plugin') . '</a></p>' .
            '</div>',
        ]);
    }

    public static function adminDashboardOptionsFormV2()
    {
        dcCore::app()->auth->user_prefs->addWorkspace(basename(__DIR__));

        echo
        '<div class="fieldset">' .
        '<h4>' . __('Activity report') . '</h4>' .
        '<p><label for="activityReport_dashboard_item">' .
        __('Number of activities to show on dashboard:') . '</label>' .
        form::combo(
            'activityReport_dashboard_item',
            self::comboList(),
            self::comboList(dcCore::app()->auth->user_prefs->__get(basename(__DIR__))->dashboard_item)
        ) . '</p>' .
        '</div>';
    }

    public static function adminAfterDashboardOptionsUpdate($user_id = null)
    {
        if (is_null($user_id)) {
            return;
        }

        dcCore::app()->auth->user_prefs->addWorkspace(basename(__DIR__));
        dcCore::app()->auth->user_prefs->__get(basename(__DIR__))->put(
            'dashboard_item',
            self::comboList(@$_POST['activityReport_dashboard_item']),
            'integer'
        );
    }

    private static function comboList($q = true)
    {
        $l = [
            __('Do not show activity report') => 0,
            5                                 => 5,
            10                                => 10,
            15                                => 15,
            20                                => 20,
            50                                => 50,
            100                               => 100,
        ];
        if (true === $q) {
            return $l;
        }
        if (!$q) {
            $q = -1;
        }

        return in_array($q, $l) ? $l[$q] : 0;
    }
}
