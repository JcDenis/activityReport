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
use dcAdmin;
use dcAuth;
use dcCore;
use dcFavorites;
use dcNsProcess;
use dcPage;
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
class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && defined('ACTIVITY_REPORT')
            && My::phpCompliant()
            && My::isInstalled();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            My::name(),
            dcCore::app()->adminurl?->get('admin.plugin.' . My::id()),
            dcPage::getPF(My::id() . '/icon.svg'),
            preg_match(
                '/' . preg_quote((string) dcCore::app()->adminurl?->get('admin.plugin.' . My::id())) . '(&.*)?$/',
                $_SERVER['REQUEST_URI']
            ),
            dcCore::app()->auth?->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_ADMIN,
            ]), dcCore::app()->blog?->id)
        );

        dcCore::app()->addBehaviors([
            // dashboard favorites icon
            'adminDashboardFavoritesV2' => function (dcFavorites $favs): void {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => dcCore::app()->adminurl?->get('admin.plugin.' . My::id()),
                    'small-icon'  => dcPage::getPF(My::id() . '/icon.svg'),
                    'large-icon'  => dcPage::getPF(My::id() . '/icon.svg'),
                    'permissions' => dcCore::app()->auth?->makePermissions([
                        dcAuth::PERMISSION_ADMIN,
                    ]),
                ]);
            },
            // dashboard content display
            'adminDashboardContentsV2' => function (ArrayObject $items): void {
                $limit = abs((int) dcCore::app()->auth?->user_prefs?->get(My::id())->get('dashboard_item'));
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
                    if (!$groups->has($rs->f('activity_group'))) {
                        continue;
                    }
                    $group   = $groups->get($rs->f('activity_group'));
                    $lines[] = '<dt title="' . __($group->title) . '">' .
                    '<strong>' . __($group->get($rs->f('activity_action'))->title) . '</strong>' .
                    '<br />' . Date::str(
                        dcCore::app()->blog?->settings->get('system')->get('date_format') . ', ' . dcCore::app()->blog?->settings->get('system')->get('time_format'),
                        (int) strtotime($rs->f('activity_dt')),
                        dcCore::app()->auth?->getInfo('user_tz')
                    ) . '<dt>' .
                    '<dd><p>' .
                    '<em>' . vsprintf(
                        __($group->get($rs->f('activity_action'))->message),
                        json_decode($rs->f('activity_logs'), true)
                    ) . '</em></p></dd>';
                }
                if (empty($lines)) {
                    return ;
                }

                $items[] = new ArrayObject([
                    '<div id="activity-report-logs" class="box medium">' .
                    '<h3>' . My::name() . '</h3>' .
                    '<dl id="reports">' . implode('', $lines) . '</dl>' .
                    '<p class="modules"><a class="module-details" href="' .
                    dcCore::app()->adminurl?->get('admin.plugin.' . My::id()) . '">' .
                    __('View all logs') . '</a> - <a class="module-config" href="' .
                    dcCore::app()->adminurl?->get('admin.plugins', [
                        'module' => My::id(),
                        'conf'   => 1,
                        'redir'  => dcCore::app()->adminurl->get('admin.home'),
                    ]) . '">' .
                    __('Configure plugin') . '</a></p>' .
                    '</div>',
                ]);
            },
            // dashboard content user preference form
            'adminDashboardOptionsFormV2' => function (): void {
                echo
                (new Div())->class('fieldset')->items([
                    (new Text('h4', My::name())),
                    (new Para())->items([
                        (new Label(__('Number of activities to show on dashboard:'), Label::OUTSIDE_LABEL_BEFORE))->for(My::id() . '_dashboard_item'),
                        (new Select(My::id() . '_dashboard_item'))->default((string) dcCore::app()->auth?->user_prefs?->get(My::id())->get('dashboard_item'))->items([
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
                    dcCore::app()->auth?->user_prefs?->get(My::id())->put(
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
