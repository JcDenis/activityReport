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

if (!defined('DC_CONTEXT_MODULE')) {
    return null;
}

$report =& $core->activityReport;
$super = $core->auth->isSuperAdmin() && !empty($_REQUEST['super']);
$redir = empty($_REQUEST['redir']) ? $list->getURL() . '#plugins' : $_REQUEST['redir'];

if ($super) {
    $report->setGlobal();
}

$combo_interval = [
    __('every hour') => 3600,
    __('every 2 hours') => 7200,
    __('2 times by day') => 43200,
    __('every day') => 86400,
    __('every 2 days') => 172800,
    __('every week') => 604800
];

$combo_obselete = [
    __('every hour') => 3600,
    __('every 2 hours') => 7200,
    __('2 times by day') => 43200,
    __('every day') => 86400,
    __('every 2 days') => 172800,
    __('every week') => 604800,
    __('every 2 weeks') => 1209600,
    __('every 4 weeks') => 2419200
];

$combo_format = [
    __('Plain text') => 'plain',
    __('HTML') => 'html'
];

if (!empty($_POST['save'])) {
    try {
        $report->setSetting('active', !empty($_POST['active']));
        $report->setSetting('dashboardItem', !empty($_POST['dashboardItem']));
        if (in_array($_POST['interval'], $combo_interval)) {
            $report->setSetting('interval', (integer) $_POST['interval']);
        }
        if (in_array($_POST['obsolete'], $combo_obselete)) {
            $report->setSetting('obsolete',(integer) $_POST['obsolete']);
        }
        $report->setSetting('mailinglist', explode(';',$_POST['mailinglist']));
        $report->setSetting('mailformat', isset($_POST['mailformat']) && $_POST['mailformat'] == 'html' ? 'html' : 'plain');
        $report->setSetting('dateformat', html::escapeHTML($_POST['dateformat']));
        $report->setSetting('requests', isset($_POST['requests']) ? $_POST['requests'] : []);
        $report->setSetting('blogs', isset($_POST['blogs']) ? $_POST['blogs'] : []);

        if (!empty($_POST['force_report'])) {
            $core->activityReport->needReport(true);
        }
        if (!empty($_POST['force_delete'])) {
            $core->activityReport->deleteLogs();
        }

        dcPage::addSuccessNotice(
            __('Configuration has been successfully updated.')
        );
        $core->adminurl->redirect('admin.plugins', ['module' => 'activityReport', 'conf' => 1, 'super' => $super]);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

$last_report_ts = $report->getSetting('lastreport');
if (!$last_report_ts) {
    $last_report = __('never');
    $next_report = __('on new activity');
} else {
    $last_report = dt::str(
        $core->blog->settings->system->date_format . ', ' . $core->blog->settings->system->time_format, 
        $last_report_ts, 
        $core->auth->getInfo('user_tz')
    );
    $next_report = dt::str(
        $core->blog->settings->system->date_format . ', ' . $core->blog->settings->system->time_format, 
        (integer) $report->getSetting('interval') + $next_report_ts, 
        $core->auth->getInfo('user_tz')
    );
}
$emails = implode(';', $report->getSetting('mailinglist'));

if ($core->auth->isSuperAdmin()) {
    echo sprintf(
        '<p class="modules right"><a class="module-config" href="%s">%s</a><br class="clear"/></p>' ,
        $core->adminurl->get('admin.plugins', ['module' => 'activityReport', 'conf' => 1, 'super' => !$super]),
        sprintf(__('Configure activity report for %s'), $super ? __('current blog') : _('all blogs'))
    );
}
if (activityReport::hasMailer()) {
    echo '<p class="message">' .
        __('This server has no mail function, activityReport does not send email report.') .
        '</p>';
}

echo '
<div class="fieldset two-cols" id="settings"><h4>' . __('Settings') . '</h4>
<div class="col">
<p><label class="classic" for="active">' .
form::checkbox('active', '1', $report->getSetting('active')).' '.
($super ? 
    __('Enable super administrator report') :
    __('Enable report on this blog')
) . '</label></p>';

if (!$super) {
    echo 
    '<p><label class="classic" for="dashboardItem">' .
    form::checkbox('dashboardItem', 1, $report->getSetting('dashboardItem')).' '.
    __('Add activity report on dashboard items') . '</label></p>';
}
echo '
<p><label for="obselete">' . __('Automatic cleaning of old logs:') . '</label>' .
form::combo('obsolete', $combo_obselete, $report->getSetting('obsolete')) . '</p>

<p><label for="dateformat">' . __('Date format:') . '<br />'.
form::field('dateformat', 60, 255, $report->getSetting('dateformat')) . '</label></p>
<p class="form-note">' . __('Use Dotclear date formaters. ex: %B %d at %H:%M') . '</p>' .

form::hidden(['super'], $super);

if (!$super) {
    echo 
    '<p><img alt="'. __('RSS feed') . '" src="' . dcPage::getPF('activityReport/inc/img/feed.png') . '" />' .
    '<a title="' . __('RSS feed') . '" href="' . 
    $core->blog->url . $core->url->getBase('activityReport') . '/rss2/' . $report->getUserCode() . '">' .
    __('Rss2 feed for activity on this blog') . '</a><br />' .
    '<img alt="' . __('Atom feed') . '" src="' . dcPage::getPF('activityReport/inc/img/feed.png') . '" />' .
    '<a title="' . __('Atom feed') . '" href="' . 
    $core->blog->url . $core->url->getBase('activityReport') . '/atom/' . $report->getUserCode() . '">' .
    __('Atom feed for activity on this blog') . '</a></p>';
}
echo '
</div><div class="col">

<p><label for="interval">' . __('Send report:').'</label>' .
form::combo('interval', $combo_interval, $report->getSetting('interval')) . '</p>

<p><label for="mailinglist">' . __('Recipients:') . '<br />'.
form::field('mailinglist', 60, 255, $emails) . '</label></p>
<p class="form-note">' . __('Separate multiple email addresses with a semicolon ";"') . '</p>

<p><label for="mailformat">' .  __('Report format:') . '</label>' .
form::combo('mailformat', $combo_format, $report->getSetting('mailformat')) . '</p>

<ul>
<li>' . __('Last report by email:') . ' ' . $last_report . '</li>
<li>' . __('Next report by email:') . ' ' . $next_report . '</li>
</ul>
</div><br class="clear"/>
</div><br class="clear"/>';

if ($super) {
    echo '
    <div class="fieldset one-box" id="setting_blog"><h4>' . __('Blogs') . '</h4>
    <p>' . __('Select blogs to add to report') . '</p>';

    $i = $j = 0;
    $selected_blogs = $report->getSetting('blogs');
    $blogs = $core->getBlogs();
    $num_blogs = $blogs->count();
    while($blogs->fetch()) {
        $blog_id = $core->con->escape($blogs->blog_id);

        echo '
        <div class="fieldset box">
        <p><label class="classic" for="blogs_' . $i . '_">' .
        form::checkbox(
            ['blogs['.$i.']', 'blogs_' . $i . '_'],
            $blog_id,
            in_array($blog_id,$selected_blogs)
        ) . ' ' . 
        $blogs->blog_name . ' (' . $blog_id . ')</label></p>
        </div>';

        $i++;
    }
    echo '</div>';
}
echo '
<div class="fieldset one-box" id="setting_report"><h4>' . __('Report') . '</h4>
<p>' . __('Select actions by activity type to add to report') . '</p>';

$groups = $report->getGroups();
$blog_request = $report->getSetting('requests');

$i = 0;
foreach($groups as $group_id => $group) {
    echo '<div class="fieldset box"><h5>'. __($group['title']) . '</h5>';

    foreach($group['actions'] as $action_id => $action) {
        echo '
        <p><label class="classic" for="requests_' . $group_id . '_' . $action_id . '_">' .
        form::checkbox(
            ['requests[' . $group_id . '][' . $action_id . ']', 'requests_' . $group_id . '_' . $action_id . '_'],
            1,
            isset($blog_request[$group_id][$action_id])
        ) . ' ' . __($action['title']) . '</label></p>';
    }
    echo '</div>';
}
echo '</div>';

$report->unsetGlobal();