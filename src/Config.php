<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Hidden,
    Input,
    Label,
    Note,
    Para,
    Select,
    Text
};
use Exception;

/**
 * @brief       activityReport config class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Config extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::CONFIG));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (empty($_POST['save'])) {
            return true;
        }

        try {
            $s = ActivityReport::instance()->settings;

            $s->set('feed_active', !empty($_POST['feed_active']));
            if (in_array($_POST['interval'], Combo::interval())) {
                $s->set('interval', (int) $_POST['interval']);
            }
            if (in_array($_POST['obsolete'], Combo::obsolete())) {
                $s->set('obsolete', (int) $_POST['obsolete']);
            }
            $s->set('mailinglist', explode(';', $_POST['mailinglist']));
            $s->set('mailformat', isset($_POST['mailformat']) && $_POST['mailformat'] == 'html' ? 'html' : 'plain');
            $s->set('dateformat', $_POST['dateformat']);
            $s->set('requests', $_POST['requests'] ?? []);

            Notices::addSuccessNotice(
                __('Configuration successfully updated.')
            );

            if (!empty($_POST['send_report_now'])) {
                ActivityReport::instance()->needReport(true);

                Notices::addSuccessNotice(
                    __('Report successfully sent.')
                );
            }
            if (!empty($_POST['delete_report_now'])) {
                ActivityReport::instance()->deleteLogs();

                Notices::addSuccessNotice(
                    __('Logs successfully deleted.')
                );
            }

            App::backend()->url()->redirect('admin.plugins', [
                'module' => My::id(),
                'conf'   => 1,
            ]);
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $s  = ActivityReport::instance()->settings;
        $tz = is_string(App::auth()->getInfo('user_tz')) ? App::auth()->getInfo('user_tz') : 'UTC';

        if (!$s->lastreport) {
            $last_report = __('never');
            $next_report = __('on new activity');
        } else {
            $last_report = Date::str(
                App::blog()->settings()->get('system')->get('date_format') . ', ' . App::blog()->settings()->get('system')->get('time_format'),
                $s->lastreport,
                $tz
            );
            $next_report = Date::str(
                App::blog()->settings()->get('system')->get('date_format') . ', ' . App::blog()->settings()->get('system')->get('time_format'),
                $s->interval + $s->lastreport,
                $tz
            );
        }

        if (!ActivityReport::hasMailer()) {
            echo '<p class="message">' .
                __('This server has no mail function, activityReport does not send email report.') .
                '</p>';
        }

        echo
        (new Div())->class('two-boxes')->separator('')->items([
            (new Div())->class('fieldset box odd')->items([
                (new Text('h4', __('Mail report'))),
                (new Para())->items([
                    (new Label(__('Send report:'), Label::OUTSIDE_LABEL_BEFORE))->for('interval'),
                    (new Select('interval'))->default((string) $s->interval)->items(Combo::interval()),
                ]),
                (new Para())->items([
                    (new Label(__('Recipients:'), Label::OUTSIDE_LABEL_BEFORE))->for('mailinglist'),
                    (new Input('mailinglist'))->size(60)->maxlength(255)->value(implode(';', $s->mailinglist)),
                ]),
                (new Note())->class('form-note')->text(__('Separate multiple email addresses with a semicolon ";"')),
                (new Note())->class('form-note')->text(__('Leave it empty to disable mail report.')),
                (new Para())->items([
                    (new Label(__('Date format:'), Label::OUTSIDE_LABEL_BEFORE))->for('dateformat'),
                    (new Input('dateformat'))->size(60)->maxlength(255)->value($s->dateformat),
                ]),
                (new Note())->class('form-note')->text(__('Use Dotclear date formaters. ex: %B %d at %H:%M')),
                (new Para())->items([
                    (new Label(__('Report format:'), Label::OUTSIDE_LABEL_BEFORE))->for('mailformat'),
                    (new Select('mailformat'))->default($s->mailformat)->items(Combo::mailformat()),
                ]),
                (new Text(
                    'ul',
                    '<li>' . __('Last report by email:') . ' ' . $last_report . '</li>' .
                    '<li>' . __('Next report by email:') . ' ' . $next_report . '</li>'
                )),
            ]),
            (new Div())->class('fieldset box even')->items([
                (new Text('h4', __('Feeds'))),
                (new Para())->items([
                    (new Checkbox('feed_active', $s->feed_active))->value(1),
                    (new Label(__('Enable activity feed'), Label::OUTSIDE_LABEL_AFTER))->for('feed_active')->class('classic'),
                ]),
                (new Text(
                    'ul',
                    '<li><img alt="' . __('RSS feed') . '" src="' . My::fileURL('img/feed.png') . '" /> ' .
                    '<a title="' . __('RSS feed') . '" href="' .
                    App::blog()->url() . App::url()->getBase(My::id()) . '/rss2/' . ActivityReport::instance()->getUserCode() . '">' .
                    __('Rss2 activities feed') . '</a></li>' .
                    '<li><img alt="' . __('Atom feed') . '" src="' . My::fileURL('img/feed.png') . '" /> ' .
                    '<a title="' . __('Atom feed') . '" href="' .
                    App::blog()->url() . App::url()->getBase(My::id()) . '/atom/' . ActivityReport::instance()->getUserCode() . '">' .
                    __('Atom activities feed') . '</a></li>'
                )),
            ]),
        ])->render();

        $i = 0;
        $g = [
            (new Text('h4', __('Activities'))),
            (new Text('p', __('Select actions by activity type to add to report'))),
        ];
        foreach (ActivityReport::instance()->groups->dump() as $group_id => $group) {
            $a   = [];
            $a[] = (new Text('h5', __($group->title)));
            foreach ($group->dump() as $action_id => $action) {
                $a[] = (new Para())->items([
                    (new Checkbox(
                        ['requests[' . $group_id . '][' . $action_id . ']', 'requests_' . $group_id . '_' . $action_id . '_'],
                        isset($s->requests[$group_id][$action_id])
                    ))->value(1),
                    (new Label(__($action->title), Label::OUTSIDE_LABEL_AFTER))->for(
                        'requests_' . $group_id . '_' . $action_id . '_'
                    )->class('classic'),
                ]);
            }
            $g[] = (new Div())->class('fieldset box')->items($a);
        }
        echo
        (new Div('setting_report'))->class('fieldset one-box')->items($g)->render();

        echo
        (new Div('settings'))->class('fieldset')->items([
            (new Text('h4', __('Maintenance'))),
            (new Para())->items([
                (new Label(__('Automatic cleaning of old logs:'), Label::OUTSIDE_LABEL_BEFORE))->for('obsolete'),
                (new Select('obsolete'))->default((string) $s->obsolete)->items(Combo::obsolete()),
            ]),
            (new Para())->items([
                (new Checkbox('send_report_now'))->value(1),
                (new Label(__('Send report now'), Label::OUTSIDE_LABEL_AFTER))->for('send_report_now')->class('classic'),
            ]),
            (new Para())->items([
                (new Checkbox('delete_report_now'))->value(1),
                (new Label(__('Delete all logs now'), Label::OUTSIDE_LABEL_AFTER))->for('delete_report_now')->class('classic'),
            ]),
        ])->render();
    }
}
