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

$_menu['Plugins']->addItem(
    __('Activity report'),
    $core->adminurl->get('admin.plugin.activityReport'),
    dcPage::getPF('activityReport/icon.png'),
    preg_match(
        '/' . preg_quote($core->adminurl->get('admin.plugin.activityReport')) . '(&.*)?$/', 
        $_SERVER['REQUEST_URI']
    ),
    $core->auth->check('admin',$core->blog->id)
);

$core->addBehavior('adminDashboardOptionsForm', ['activityReportAdmin', 'adminDashboardOptionsForm']);
$core->addBehavior('adminAfterDashboardOptionsUpdate', ['activityReportAdmin', 'adminAfterDashboardOptionsUpdate']);

class activityReportAdmin
{
    public static function adminDashboardContents(dcCore $core, $items)
    {
        $core->auth->user_prefs->addWorkspace('activityReport');
        $limit = abs((integer) $core->auth->user_prefs->activityReport->dashboard_item);
        if (!$limit) {
            return null;
        }
        $p = [
            'limit' => $limit,
            'order' => 'activity_dt DESC',
            'sql' => $core->activityReport->requests2params($core->activityReport->getSetting('requests'))
        ];
        $lines = [];
        $rs = $core->activityReport->getLogs($p);
        if ($rs->isEmpty()) {
            return null;
        }
        $groups = $core->activityReport->getGroups();
        while($rs->fetch()) {
            $group = $rs->activity_group;

            if (!isset($groups[$group])) {
                continue;
            }
            $lines[] = 
            '<dt title="' . __($groups[$group]['title']) . '">' .
            '<strong>' . __($groups[$group]['actions'][$rs->activity_action]['title']) . '</strong>' .
            '<br />' . dt::str(
                $core->blog->settings->system->date_format . ', ' . $core->blog->settings->system->time_format,
                strtotime($rs->activity_dt),
                $core->auth->getInfo('user_tz')
            ) . '<dt>' .
            '<dd><p>' .
            '<em>' .vsprintf(
                __($groups[$group]['actions'][$rs->activity_action]['msg']),
                $core->activityReport->decode($rs->activity_logs)
            ) . '</em></p></dd>';
        }
        if (empty($lines)) {
            return null;
        }
        $items[] = new ArrayObject([
            '<div id="activity-report-logs" class="box medium">' .
            '<h3>' . __('Activity report') . '</h3>' .
            '<dl id="reports">' . implode('', $lines) . '</dl>' .
            '<p><a href="'.$core->adminurl->get('admin.plugin.activityReport') .'">' . 
            __('View all logs') . '</a></p>' .
            '</div>'
        ]);
    }

    public static function adminDashboardOptionsForm(dcCore $core)
    {
        $core->auth->user_prefs->addWorkspace('activityReport');

        echo
        '<div class="fieldset">' .
        '<h4>' . __('Activity report') . '</h4>' .
        '<p><label for="activityReport_dashboard_item">' . 
        __('Number of activities to show on dashboard:') . '</label>' .
        form::combo(
            'activityReport_dashboard_item', 
            self::comboList(), 
            self::comboList($core->auth->user_prefs->activityReport->dashboard_item)
        ) . '</p>' .
        '</div>';
    }

    public static function adminAfterDashboardOptionsUpdate($user_id = null)
    {
        global $core;

        if (is_null($user_id)) {
            return;
        }

        $core->auth->user_prefs->addWorkspace('activityReport');
        $core->auth->user_prefs->activityReport->put(
            'dashboard_item', 
            self::comboList(@$_POST['activityReport_dashboard_item']), 
            'integer'
        );
    }

    private static function comboList($q = true)
    {
        $l = [
            __('Do not show activity report') => 0,
            5 => 5,
            10 => 10,
            15 => 15,
            20 => 20,
            50 => 50,
            100 => 100
        ];
        if (true === $q) {
            return $l;
        }
        return in_array($q, $l) ? $l[$q] : 0;
    }
}