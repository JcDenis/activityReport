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
declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use ArrayObject;
use dcAuth;
use dcCore;
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
 * Backend process
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

        dcCore::app()->addBehaviors([
            // dashboard favorites icon
            'adminDashboardFavoritesV2' => function (Favorites $favs): void {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => dcCore::app()->auth->makePermissions([
                        dcAuth::PERMISSION_ADMIN,
                    ]),
                ]);
            },
            // dashboard content display
            'adminDashboardContentsV2' => function (ArrayObject $items): void {
                $db    = dcCore::app()->auth->user_prefs?->get(My::id())->get('dashboard_item');
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
                        dcCore::app()->blog?->settings->get('system')->get('date_format') . ', ' . dcCore::app()->blog?->settings->get('system')->get('time_format'),
                        (int) strtotime($row->dt),
                        is_string(dcCore::app()->auth->getInfo('user_tz')) ? dcCore::app()->auth->getInfo('user_tz') : 'UTC'
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
                    dcCore::app()->admin->url->get('admin.plugins', [
                        'module' => My::id(),
                        'conf'   => 1,
                        'redir'  => dcCore::app()->admin->url->get('admin.home'),
                    ]) . '">' .
                    __('Configure plugin') . '</a></p>' .
                    '</div>',
                ]);
            },
            // dashboard content user preference form
            'adminDashboardOptionsFormV2' => function (): void {
                $db = dcCore::app()->auth->user_prefs?->get(My::id())->get('dashboard_item');
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
                    dcCore::app()->auth->user_prefs?->get(My::id())->put(
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
