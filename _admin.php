<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of activityReport, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2010 JC Denis and contributors
# jcdenis@gdwd.com
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')){return;}

if (!defined('ACTIVITY_REPORT')){return;}

# Plugin menu
$_menu['Plugins']->addItem(
	__('Activity report'),
	'plugin.php?p=activityReport','index.php?pf=activityReport/icon.png',
	preg_match('/plugin.php\?p=activityReport(&.*)?$/',
	$_SERVER['REQUEST_URI']),
	$core->auth->check('admin',$core->blog->id)
);

# Dashboarditems
if ($core->activityReport->getSetting('dashboardItem'))
{
	$core->addBehavior(
		'adminDashboardHeaders',
		array('activityReportAdmin','dashboardHeaders')
	);
	$core->addBehavior(
		'adminDashboardItems',
		array('activityReportAdmin','dashboardItems')
	);
}

class activityReportAdmin
{
	# Add CSS to dashboardHeaders for items
	public static function dashboardHeaders()
	{
		return
		"\n<!-- CSS for activityReport --> \n".
		"<style type=\"text/css\"> \n".
		"#dashboard-items #report dt { font-weight: bold; margin: 0 0 0.4em 0; } \n".
		"#dashboard-items #report dd { font-size: 0.9em; margin: 0 0 1em 0; } \n".
		"#dashboard-items #report dd p { margin: 0.2em 0 0 0; } \n".
		"</style> \n";
	}

	# Add report to dashboardItems
	public static function dashboardItems($core, $__dashboard_items)
	{
		$r = $core->activityReport->getSetting('requests');
		$g = $core->activityReport->getGroups();

		$p = array();
		$p['limit'] = 20;
		$p['order'] = 'activity_dt DESC';
		$p['sql'] = $core->activityReport->requests2params($r);

		$res = '';
		$rs = $core->activityReport->getLogs($p);
		if (!$rs->isEmpty())
		{
			while($rs->fetch())
			{
				$group = $rs->activity_group;

				if (!isset($g[$group])) continue;

				$res .= 
				'<dd><p title="'.__($g[$group]['title']).'"><strong>'.
				__($g[$group]['actions'][$rs->activity_action]['title']).
				'</p></strong><em>'.
				vsprintf(
					__($g[$group]['actions'][$rs->activity_action]['msg']),
					$core->activityReport->decode($rs->activity_logs)
				).			
				'</em></dd>';
			}
		}
		if (!empty($res))
		{
			$__dashboard_items[1][] = 
				'<h3>'.__('Activity report').'</h3>'.
				'<dl id="report">'.$res.'</dl>';
		}
	}
}
?>