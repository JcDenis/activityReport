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

if (!defined('DC_RC_PATH')){return;}

global $__autoload, $core;

$__autoload['activityReport'] = 
	dirname(__FILE__).'/inc/class.activity.report.php';

try
{
	$core->activityReport = new activityReport($core);

	$core->url->register(
		'activityReport',
		'reports',
		'^reports/((atom|rss2)/(.+))$',
		array('activityReportPublicUrl','feed')
	);

	define('ACTIVITY_REPORT',true);

	require_once dirname(__FILE__).'/inc/class.activity.report.behaviors.php';
}
catch (Exception $e) {
	//throw new Exception('Failed to launch activityReport');
}
?>