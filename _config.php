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

$super = dcCore::app()->auth->isSuperAdmin() && !empty($_REQUEST['super']);
$redir = empty($_REQUEST['redir']) ? dcCore::app()->admin->list->getURL() . '#plugins' : $_REQUEST['redir'];

if ($super) {
    dcCore::app()->activityReport->setGlobal();
}

$combo_interval = [
    __('every hour')     => 3600,
    __('every 2 hours')  => 7200,
    __('2 times by day') => 43200,
    __('every day')      => 86400,
    __('every 2 days')   => 172800,
    __('every week')     => 604800,
];

$combo_obselete = [
    __('every hour')     => 3600,
    __('every 2 hours')  => 7200,
    __('2 times by day') => 43200,
    __('every day')      => 86400,
    __('every 2 days')   => 172800,
    __('every week')     => 604800,
    __('every 2 weeks')  => 1209600,
    __('every 4 weeks')  => 2419200,
];

$combo_format = [
    __('Plain text') => 'plain',
    __('HTML')       => 'html',
];

if (!empty($_POST['save'])) {
    try {
        dcCore::app()->activityReport->setSetting('active', !empty($_POST['active']));
        if (in_array($_POST['interval'], $combo_interval)) {
            dcCore::app()->activityReport->setSetting('interval', (int) $_POST['interval']);
        }
        if (in_array($_POST['obsolete'], $combo_obselete)) {
            dcCore::app()->activityReport->setSetting('obsolete', (int) $_POST['obsolete']);
        }
        dcCore::app()->activityReport->setSetting('mailinglist', explode(';', $_POST['mailinglist']));
        dcCore::app()->activityReport->setSetting('mailformat', isset($_POST['mailformat']) && $_POST['mailformat'] == 'html' ? 'html' : 'plain');
        dcCore::app()->activityReport->setSetting('dateformat', html::escapeHTML($_POST['dateformat']));
        dcCore::app()->activityReport->setSetting('requests', $_POST['requests'] ?? []);
        dcCore::app()->activityReport->setSetting('blogs', $_POST['blogs'] ?? []);

        if (!empty($_POST['send_report_now'])) {
            dcCore::app()->activityReport->needReport(true);

            dcAdminNotices::addSuccessNotice(
                __('Report successfully sent.')
            );
        }
        if (!empty($_POST['delete_report_now'])) {
            dcCore::app()->activityReport->deleteLogs();

            dcAdminNotices::addSuccessNotice(
                __('Logs successfully deleted.')
            );
        }

        dcAdminNotices::addSuccessNotice(
            __('Configuration successfully updated.')
        );
        dcCore::app()->adminurl->redirect('admin.plugins', ['module' => 'activityReport', 'conf' => 1, 'super' => $super]);
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

$last_report_ts = dcCore::app()->activityReport->getSetting('lastreport');
if (!$last_report_ts) {
    $last_report = __('never');
    $next_report = __('on new activity');
} else {
    $last_report = dt::str(
        dcCore::app()->blog->settings->system->date_format . ', ' . dcCore::app()->blog->settings->system->time_format,
        $last_report_ts,
        dcCore::app()->auth->getInfo('user_tz')
    );
    $next_report = dt::str(
        dcCore::app()->blog->settings->system->date_format . ', ' . dcCore::app()->blog->settings->system->time_format,
        (int) dcCore::app()->activityReport->getSetting('interval') + $last_report_ts,
        dcCore::app()->auth->getInfo('user_tz')
    );
}
$emails = implode(';', dcCore::app()->activityReport->getSetting('mailinglist'));

if (dcCore::app()->auth->isSuperAdmin()) {
    echo sprintf(
        '<p class="modules right"><a class="module-config" href="%s">%s</a><br class="clear"/></p>',
        dcCore::app()->adminurl->get('admin.plugins', ['module' => 'activityReport', 'conf' => 1, 'super' => !$super]),
        sprintf(__('Configure activity report for %s'), $super ? __('current blog') : _('all blogs'))
    );
}
if (!activityReport::hasMailer()) {
    echo '<p class="message">' .
        __('This server has no mail function, activityReport does not send email report.') .
        '</p>';
}

echo '
<div class="fieldset two-cols" id="settings"><h4>' . __('Settings') . '</h4>
<div class="col">

<p><label class="classic" for="active">' .
form::checkbox('active', '1', dcCore::app()->activityReport->getSetting('active')) . ' ' .
(
    $super ?
    __('Enable super administrator report') :
    __('Enable report on this blog')
) . '</label></p>

<p><label for="obselete">' . __('Automatic cleaning of old logs:') . '</label>' .
form::combo('obsolete', $combo_obselete, dcCore::app()->activityReport->getSetting('obsolete')) . '</p>

<p><label for="dateformat">' . __('Date format:') . '<br />' .
form::field('dateformat', 60, 255, dcCore::app()->activityReport->getSetting('dateformat')) . '</label></p>
<p class="form-note">' . __('Use Dotclear date formaters. ex: %B %d at %H:%M') . '</p>' .

form::hidden(['super'], $super);

if (!$super) {
    echo
    '<p><img alt="' . __('RSS feed') . '" src="' . dcPage::getPF('activityReport/inc/img/feed.png') . '" />' .
    '<a title="' . __('RSS feed') . '" href="' .
    dcCore::app()->blog->url . dcCore::app()->url->getBase('activityReport') . '/rss2/' . dcCore::app()->activityReport->getUserCode() . '">' .
    __('Rss2 feed for activity on this blog') . '</a><br />' .
    '<img alt="' . __('Atom feed') . '" src="' . dcPage::getPF('activityReport/inc/img/feed.png') . '" />' .
    '<a title="' . __('Atom feed') . '" href="' .
    dcCore::app()->blog->url . dcCore::app()->url->getBase('activityReport') . '/atom/' . dcCore::app()->activityReport->getUserCode() . '">' .
    __('Atom feed for activity on this blog') . '</a></p>';
}
echo '
</div><div class="col">

<p><label for="interval">' . __('Send report:') . '</label>' .
form::combo('interval', $combo_interval, dcCore::app()->activityReport->getSetting('interval')) . '</p>

<p><label for="mailinglist">' . __('Recipients:') . '<br />' .
form::field('mailinglist', 60, 255, $emails) . '</label></p>
<p class="form-note">' . __('Separate multiple email addresses with a semicolon ";"') . '</p>

<p><label for="mailformat">' . __('Report format:') . '</label>' .
form::combo('mailformat', $combo_format, dcCore::app()->activityReport->getSetting('mailformat')) . '</p>

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

    $i              = $j = 0;
    $selected_blogs = dcCore::app()->activityReport->getSetting('blogs');
    $blogs          = dcCore::app()->getBlogs();
    $num_blogs      = $blogs->count();
    while ($blogs->fetch()) {
        $blog_id = dcCore::app()->con->escape($blogs->blog_id);

        echo '
        <div class="fieldset box">
        <p><label class="classic" for="blogs_' . $i . '_">' .
        form::checkbox(
            ['blogs[' . $i . ']', 'blogs_' . $i . '_'],
            $blog_id,
            in_array($blog_id, $selected_blogs)
        ) . ' ' .
        $blogs->blog_name . ' (' . $blog_id . ')</label></p>
        </div>';

        $i++;
    }
    echo '</div>';
} else {
    echo form::hidden('blogs[0]', dcCore::app()->blog->id);
}
echo '
<div class="fieldset one-box" id="setting_report"><h4>' . __('Report') . '</h4>
<p>' . __('Select actions by activity type to add to report') . '</p>';

$groups       = dcCore::app()->activityReport->getGroups();
$blog_request = dcCore::app()->activityReport->getSetting('requests');

$i = 0;
foreach ($groups as $group_id => $group) {
    echo '<div class="fieldset box"><h5>' . __($group['title']) . '</h5>';

    foreach ($group['actions'] as $action_id => $action) {
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

if (1) {
    echo '
    <div class="fieldset" id="settings"><h4>' . __('Special') . '</h4>

    <p><label class="classic" for="send_report_now">' .
    form::checkbox('send_report_now', '1', false) . ' ' .
    __('Send report now') . '</label></p>

    <p><label class="classic" for="delete_report_now">' .
    form::checkbox('delete_report_now', '1', false) . ' ' .
    __('Delete all logs now') . '</label></p>

    </div>';
}

dcCore::app()->activityReport->unsetGlobal();
