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

$new_version = $core->plugins->moduleInfo('activityReport','version');
$old_version = $core->getVersion('activityReport');

if (version_compare($old_version,$new_version,'>=')) {return;}

try
{
	# Check DC version
	if (version_compare(DC_VERSION,'2.2-beta','<'))
	{
		throw new Exception('translater requires Dotclear 2.2');
	}
	
	# Table
	$s = new dbStruct($core->con,$core->prefix);
	$s->activity
		->activity_id ('bigint',0,false)
		->activity_type ('varchar',32,false,"'activityReport'")
		->blog_id ('varchar',32,true)
		->activity_group('varchar',32,false)
		->activity_action ('varchar',32,false)
		->activity_logs ('text',0,false)
		->activity_dt ('timestamp',0,false,'now()')
		->activity_blog_status ('smallint',0,false,0)
		->activity_super_status ('smallint',0,false,0)
		
		->primary('pk_activity','activity_id')
		->index('idx_activity_type','btree','activity_type')
		->index('idx_activity_blog_id','btree','blog_id')
		->index('idx_activity_action','btree','activity_group','activity_action')
		->index('idx_activity_blog_status','btree','activity_blog_status')
		->index('idx_activity_super_status','btree','activity_super_status');
	
	$s->activity_setting
		->setting_id('varchar',64,false)
		->blog_id ('varchar',32,true)
		->setting_type('varchar',32,false)
		->setting_value('text',0,false)
		
		->unique('uk_activity_setting','setting_id','blog_id','setting_type')
		->index('idx_activity_setting_blog_id','btree','blog_id')
		->index('idx_activity_setting_type','btree','setting_type');
	
	$si = new dbStruct($core->con,$core->prefix);
	$changes = $si->synchronize($s);

	# Version
	$core->setVersion('activityReport',$new_version);

	return true;
}
catch (Exception $e)
{
	$core->error->add($e->getMessage());
}
return false;
?>