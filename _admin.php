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

# Plugin menu
$_menu['Plugins']->addItem(
    __('Activity report'),
    $core->adminurl->get('admin.plugin.activityReport'),
    dcPage::getPF('activityReport/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.activityReport')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('admin',$core->blog->id)
);

# Dashboarditems
if ($core->activityReport->getSetting('dashboardItem')) {
    $core->addBehavior('adminDashboardHeaders', ['activityReportAdmin', 'dashboardHeaders']);
    $core->addBehavior('adminDashboardItems', ['activityReportAdmin', 'dashboardItems']);
}

class activityReportAdmin
{
    /**
     *  Add CSS to dashboardHeaders for items
     */
    public static function dashboardHeaders()
    {
        return dcPage::jsLoad('index.php?pf=activityReport/style.css');
    }

    /**
     *  Add report to dashboardItems
     */
    public static function dashboardItems(dcCore $core, $__dashboard_items)
    {
        $r = $core->activityReport->getSetting('requests');
        $g = $core->activityReport->getGroups();

        $p = array();
        $p['limit'] = 20;
        $p['order'] = 'activity_dt DESC';
        $p['sql'] = $core->activityReport->requests2params($r);

        $res = '';
        $rs = $core->activityReport->getLogs($p);
        if (!$rs->isEmpty()) {
            while($rs->fetch()) {
                $group = $rs->activity_group;

                if (!isset($g[$group])) {
                    continue;
                }

                $res .= 
                '<dd><p title="' . __($g[$group]['title']) . '"><strong>' .
                __($g[$group]['actions'][$rs->activity_action]['title']) .
                '</p></strong><em>' .
                vsprintf(
                    __($g[$group]['actions'][$rs->activity_action]['msg']),
                    $core->activityReport->decode($rs->activity_logs)
                ) .
                '</em></dd>';
            }
        }
        if (!empty($res)) {
            $__dashboard_items[1][] = 
                '<h3>' . __('Activity report') . '</h3>' .
                '<dl id="report">' . $res . '</dl>';
        }
    }
}