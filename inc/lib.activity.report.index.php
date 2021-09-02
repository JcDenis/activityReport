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

class activityReportLib
{
	public static function settingTab($core,$title,$global=false)
	{
		$O =& $core->activityReport;
		$section = isset($_REQUEST['section']) ? $_REQUEST['section'] : '';
		
		if ($global)
		{
			$O->setGlobal();
			$t = 'super';
		}
		else
		{
			$t = 'blog';
		}

		$combo_int = array(
			__('every hour') => 3600,
			__('every 2 hours') => 7200,
			__('2 times by day') => 43200,
			__('every day') => 86400,
			__('every 2 days') => 172800,
			__('every week') => 604800
		);

		$combo_obs = array(
			__('every hour') => 3600,
			__('every 2 hours') => 7200,
			__('2 times by day') => 43200,
			__('every day') => 86400,
			__('every 2 days') => 172800,
			__('every week') => 604800,
			__('every 2 weeks') => 1209600,
			__('every 4 weeks') => 2419200
		);

		$combo_format = array(
			__('Plain text') => 'plain',
			__('HTML') => 'html'
		);

		$redirect = false;
		if (!empty($_POST[$t.'_settings']))
		{
			# Active notification on this blog
			$O->setSetting('active',isset($_POST['active']));
			# Add dashboard items
			$O->setSetting('dashboardItem',isset($_POST['dashboardItem']));
			# Report interval
			if (in_array($_POST['interval'],$combo_int))
			{
				$O->setSetting('interval',(integer) $_POST['interval']);
			}
			# check obsolete logs interval
			if (in_array($_POST['obsolete'],$combo_obs))
			{
				$O->setSetting('obsolete',(integer) $_POST['obsolete']);
			}
			# mail list
			$O->setSetting('mailinglist',explode(';',$_POST['mailinglist']));
			# mail format
			$mailformat = isset($_POST['mailformat']) && $_POST['mailformat'] == 'html' ? 'html' : 'plain';
			$O->setSetting('mailformat',$mailformat);
			# date format
			$O->setSetting('dateformat',html::escapeHTML($_POST['dateformat']));
			# request infos
			$requests = isset($_POST['requests']) ? $_POST['requests'] : array();
			$O->setSetting('requests',$requests);
			#blogs
			$blogs = isset($_POST['blogs']) ? $_POST['blogs'] : array();
			$O->setSetting('blogs',$blogs);
			
			$redirect = true;
		}

		# force to send report now
		if (!empty($_POST[$t.'_force_report']))
		{
			$core->activityReport->needReport(true);
			$redirect = true;
		}

		# force to delete all logs now
		if (!empty($_POST[$t.'_force_delete']))
		{
			$core->activityReport->deleteLogs();
			$redirect = true;
		}

		if ($redirect)
		{
			http::redirect('plugin.php?p=activityReport&tab='.$t.'_settings&amp;section'.$section);
		}

		$bl = $O->getSetting('lastreport');
		$blog_last = !$bl ? __('never') : dt::str($core->blog->settings->system->date_format.', '.$core->blog->settings->system->time_format,$bl,$core->auth->getInfo('user_tz'));

		$bi = $O->getSetting('interval');
		$blog_next = !$bl ? __('on new activity') : dt::str($core->blog->settings->system->date_format.', '.$core->blog->settings->system->time_format,$bl+$bi,$core->auth->getInfo('user_tz'));

		$emails = implode(';',$O->getSetting('mailinglist'));

		?>
		<div class="multi-part" id="<?php echo $t; ?>_settings" title="<?php echo $title; ?>">

		<?php if (!$global) { ?>

		<p><img alt="<?php echo __('RSS feed'); ?>" src="index.php?pf=activityReport/inc/img/feed.png" />
		<a title="<?php echo __('RSS feed'); ?>" href="<?php echo $core->blog->url.$core->url->getBase('activityReport').'/rss2/'.$O->getUserCode(); ?>">
		<?php echo __('Rss2 feed for activity on this blog'); ?></a>
		<br />
		<img alt="<?php echo __('Atom feed'); ?>" src="index.php?pf=activityReport/inc/img/feed.png" />
		<a title="<?php echo __('Atom feed'); ?>" href="<?php echo $core->blog->url.$core->url->getBase('activityReport').'/atom/'.$O->getUserCode(); ?>">
		<?php echo __('Atom feed for activity on this blog'); ?></a></p>
		
		<?php } ?>

		<form id="setting-<?php echo $t; ?>-form" method="post" action="plugin.php">

		<fieldset id="setting-<?php echo $t; ?>-setting"><legend><?php echo __('Settings'); ?></legend>

		<p><label class="classic"><?php echo
		form::checkbox(array('active'),'1',
		 $O->getSetting('active')).' '.
		($global ? 
			__('Enable super administrator report') :
			__('Enable report on this blog')
		); ?>
		</label></p>
		<p><label class="classic"><?php echo __('Automatic cleaning of old logs:').'<br />'.
		 form::combo(array('obsolete'),$combo_obs,$O->getSetting('obsolete')); ?>
		</label></p>
		<?php

		if (!$global)
		{

			?>
			<p><label class="classic"><?php echo
			form::checkbox(array('dashboardItem'),'1',
				$O->getSetting('dashboardItem')).' '.
				__('Add activity report on dashboard items'); ?>
			</label></p>
			<?php

		}

		?>
		<p><label class="classic"><?php echo __('Send report:').'<br />'.
		 form::combo(array('interval'),$combo_int,$O->getSetting('interval')); ?>
		</label></p>

		<p><label class="classic"><?php echo __('Date format:').'<br />'.
		 form::field(array('dateformat'),60,255,$O->getSetting('dateformat')); ?>
		</label></p>
		<p class="form-note"><?php echo __('Use Dotclear date formaters. ex: %B %d at %H:%M'); ?></p>

		<p><label class="classic"><?php echo __('Report format:').'<br />'.
		 form::combo(array('mailformat'),$combo_format,$O->getSetting('mailformat')); ?>
		</label></p>

		<p><label class="classic"><?php echo __('Recipients:').'<br />'.
		 form::field(array('mailinglist'),60,255,$emails); ?>
		</label></p>
		<p class="form-note"><?php echo __('Separate multiple email addresses with a semicolon ";"'); ?></p>

		<ul>
		<li><?php echo __('Last report by email:').' '.$blog_last; ?></li>
		<li><?php echo __('Next report by email:').' '.$blog_next; ?></li>
		</ul>

		</fieldset>
		<?php

		if ($global)
		{
			?>
			<fieldset id="setting-<?php echo $t; ?>-blog"><legend><?php echo __('Blogs'); ?></legend>
			<div class="three-cols">
			<?php

			$i = 0;
			$selected_blogs = $O->getSetting('blogs');
			$blogs = $core->getBlogs();
			while($blogs->fetch())
			{
				$blog_id = $core->con->escape($blogs->blog_id);
				?>
				<div class="col">
				<p><label class="classic"><?php echo
				form::checkbox(array('blogs['.$i.']'),$blog_id,
				in_array($blog_id,$selected_blogs)).' '.
				$blogs->blog_name.' ('.$blog_id.')'; ?>
				</label></p>
				</div>
				<?php

				$i++;
			}

			?>
			</div>
			</fieldset>
			<?php
		}

		?>
		<fieldset id="setting-<?php echo $t; ?>-report"><legend><?php echo __('Report'); ?></legend>
		<div class="three-cols">
		<?php

		$groups = $O->getGroups();
		$blog_request = $O->getSetting('requests');

		$i = 0;
		foreach($groups as $k_group => $v_group)
		{

			?>
			<div class="col">
			<h3><?php echo __($v_group['title']); ?></h3>
			<?php

			foreach($v_group['actions'] as $k_action => $v_action)
			{
				?>
				<p><label class="classic"><?php echo 
				form::checkbox(array('requests['.$k_group.']['.$k_action.']'),'1',
				isset($blog_request[$k_group][$k_action])).' '.__($v_action['title']); ?>
				</label></p>
				<?php
			}

			?>
			</div>
			<?php

			$i++;
			if ($i == 3) {
				?></div><div class="three-cols"><?php
				$i = 0;
			}
		}

		?>
		</div>
		</fieldset>

		<p>
		 <input type="submit" name="<?php echo $t; ?>_settings" value="<?php echo __('Save'); ?>" />
		 <?php 
		 if (!empty($emails))
		 {
			?>
			<input type="submit" name="<?php echo $t; ?>_force_report" value="<?php echo __('Send report by email now'); ?>" />
			<?php
		 }
		 if ($global)
		 {
			?>
			<input type="submit" name="<?php echo $t; ?>_force_delete" value="<?php echo __('Delete all logs'); ?>" />
			<?php
		 }
		 echo 
		 form::hidden(array('p'),'activityReport').
		 form::hidden(array('tab'),$t.'_settings').
		 form::hidden(array('section'),$section).
		 $core->formNonce();
		 ?>
		</p>
		</form>
		</div>
		<?php
		$O->unsetGlobal();
	}

	public static function logTab($core,$title,$global=false)
	{
		$O =& $core->activityReport;
		if ($global)
		{
			$O->setGlobal();
			$t = 'super';
		}
		else
		{
			$t = 'blog';
		}

		$params = array();
		$logs = $O->getLogs($params);

		?>
		<div class="multi-part" id="<?php echo $t; ?>_logs" title="<?php echo $title; ?>">
		<?php

		if ($logs->isEmpty())
		{
			echo '<p>'.__('No log').'</p>';
		}
		else
		{

			?>
			<table>
			<thead>
			<tr>
			<th><?php echo __('Action'); ?></th>
			<th><?php echo __('Message'); ?></th>
			<th><?php echo __('Date'); ?></th>
			<?php if ($global) { ?>
			<th><?php echo __('Blog'); ?></th>
			<?php } ?>
			</tr>
			</thead>
			<tbody>
			<?php

			while($logs->fetch())
			{
				$off = $global && $logs->activity_blog_status == 1 ?
					' offline' : '';
				$date = dt::str(
					$core->blog->settings->system->date_format.', '.$core->blog->settings->system->time_format,
					strtotime($logs->activity_dt),
					$core->auth->getInfo('user_tz')
				);
				$action = $O->getGroups($logs->activity_group,$logs->activity_action);

				if (empty($action)) continue;

				$msg = vsprintf(__($action['msg']),$O->decode($logs->activity_logs));
				?>
				<tr class="line<?php echo $off; ?>">
				<td class="nowrap"><?php echo __($action['title']); ?></td>
				<td class="maximal"><?php echo $msg; ?></td>
				<td class="nowrap"><?php echo $date; ?></td>
				<?php if ($global) { ?>
				<td class="nowrap"><?php echo $logs->blog_id; ?></td>
				<?php } ?>
				</tr>
				<?php
			}

			?>
			</tbody>
			</table>
			<?php

		}

		?>
		</div>
		<?php

		$O->unsetGlobal();
	}
}
?>