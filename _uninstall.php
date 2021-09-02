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

$this->addUserAction(
	/* type */ 'tables',
	/* action */ 'delete',
	/* ns */ 'activity',
	/* description */ sprintf(__('delete %s table'),'"activity"')
);

$this->addUserAction(
	/* type */ 'tables',
	/* action */ 'delete',
	/* ns */ 'activity_setting',
	/* description */ sprintf(__('delete %s table'),'"activity_setting"')
);

$this->addUserAction(
	/* type */ 'plugins',
	/* action */ 'delete',
	/* ns */ 'activityReport',
	/* description */ __('delete plugin files')
);

$this->addUserAction(
	/* type */ 'versions',
	/* action */ 'delete',
	/* ns */ 'activityReport',
	/* description */ __('delete the version number')
);

$this->addDirectAction(
	/* type */ 'versions',
	/* action */ 'delete',
	/* ns */ 'activityReport',
	/* description */ sprintf(__('delete %s version number'),'activityReport')
);

$this->addDirectAction(
	/* type */ 'plugins',
	/* action */ 'delete',
	/* ns */ 'activityReport',
	/* description */ sprintf(__('delete %s plugin files'),'activityReport')
);
?>