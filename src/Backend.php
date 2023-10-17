<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\{
    Div,
    Label,
    Para,
    Select,
    Text
};

/**
 * @brief       activityReport backend class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem();

        App::behavior()->addBehaviors([
            // dashboard favorites icon
            'adminDashboardFavoritesV2' => function (Favorites $favs): void {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => App::auth()->makePermissions([
                        App::auth()::PERMISSION_ADMIN,
                    ]),
                ]);
            },
            // dashboard content display
            'adminDashboardContentsV2' => function (ArrayObject $items): void {
                $db    = App::auth()->prefs()->get(My::id())->get('dashboard_item');
                $limit = abs(is_numeric($db) ? (int) $db : 0);
                if (!$limit) {
                    return ;
                }

                $params = new ArrayObject([
                    'limit'    => $limit,
                    'requests' => true,
                ]);
                $rs = ActivityReport::instance()->getLogs($params);
                if (!$rs || $rs->isEmpty()) {
                    return;
                }

                $lines  = [];
                $groups = ActivityReport::instance()->groups;
                while ($rs->fetch()) {
                    $row = new ActivityRow($rs);
                    if (!$groups->has($row->group)) {
                        continue;
                    }
                    $group   = $groups->get($row->group);
                    $lines[] = '<dt title="' . __($group->title) . '">' .
                    '<strong>' . __($group->get($row->action)->title) . '</strong>' .
                    '<br />' . Date::str(
                        App::blog()->settings()->get('system')->get('date_format') . ', ' . App::blog()->settings()->get('system')->get('time_format'),
                        (int) strtotime($row->dt),
                        is_string(App::auth()->getInfo('user_tz')) ? App::auth()->getInfo('user_tz') : 'UTC'
                    ) . '<dt>' .
                    '<dd><p>' .
                    '<em>' . ActivityReport::parseMessage(__($group->get($row->action)->message), $row->logs) . '</em></p></dd>';
                }
                if (empty($lines)) {
                    return ;
                }

                $items[] = new ArrayObject([
                    '<div id="activity-report-logs" class="box medium">' .
                    '<h3>' . My::name() . '</h3>' .
                    '<dl id="reports">' . implode('', $lines) . '</dl>' .
                    '<p class="modules"><a class="module-details" href="' .
                    My::manageUrl() . '">' .
                    __('View all logs') . '</a> - <a class="module-config" href="' .
                    App::backend()->url()->get('admin.plugins', [
                        'module' => My::id(),
                        'conf'   => 1,
                        'redir'  => App::backend()->url()->get('admin.home'),
                    ]) . '">' .
                    __('Configure plugin') . '</a></p>' .
                    '</div>',
                ]);
            },
            // dashboard content user preference form
            'adminDashboardOptionsFormV2' => function (): void {
                $db = App::auth()->prefs()->get(My::id())->get('dashboard_item');
                echo
                (new Div())->class('fieldset')->items([
                    (new Text('h4', My::name())),
                    (new Para())->items([
                        (new Label(__('Number of activities to show on dashboard:'), Label::OUTSIDE_LABEL_BEFORE))->for(My::id() . '_dashboard_item'),
                        (new Select(My::id() . '_dashboard_item'))->default(is_string($db) ? $db : '')->items([
                            __('Do not show activity report') => 0,
                            5                                 => 5,
                            10                                => 10,
                            15                                => 15,
                            20                                => 20,
                            50                                => 50,
                            100                               => 100,
                        ]),
                    ]),
                ])->render();
            },
            // save dashboard content user preference
            'adminAfterDashboardOptionsUpdate' => function (?string $user_id = null): void {
                if (!is_null($user_id)) {
                    App::auth()->prefs()->get(My::id())->put(
                        'dashboard_item',
                        (int) $_POST[My::id() . '_dashboard_item'],
                        'integer'
                    );
                }
            },
            // list filters
            'adminFiltersListsV2' => function (ArrayObject $sorts): void {
                $sorts[My::id()] = [
                    My::name(),
                    [
                        __('Group')  => 'activity_group',
                        __('Action') => 'activity_action',
                        __('Date')   => 'activity_dt',
                        __('Status') => 'activity_status',
                    ],
                    'activity_dt',
                    'desc',
                    [__('logs per page'), 30],
                ];
            },
            // list columns user preference
            'adminColumnsListsV2' => function (ArrayObject $cols): void {
                $cols[My::id()] = [
                    My::name(),
                    [
                        'activity_group'  => [true, __('Group')],
                        'activity_action' => [true, __('Action')],
                        'activity_dt'     => [true, __('Date')],
                        'activity_status' => [false, __('Status')],
                    ],
                ];
            },
        ]);

        return true;
    }
}
