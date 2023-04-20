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
use adminGenericFilter;
use dcAuth;
use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Html\Form\{
    Form,
    Hidden,
    Para,
    Submit,
    Text
};
use Exception;

/**
 * Manage process (admin logs list).
 */
class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && defined('ACTIVITY_REPORT')
            && My::phpCompliant()
            && dcCore::app()->auth?->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_ADMIN,
            ]), dcCore::app()->blog?->id);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        if (!empty($_POST['delete_all_logs']) || !empty($_POST['delete_reported_logs'])) {
            try {
                ActivityReport::instance()->deleteLogs(!empty($_POST['delete_reported_logs']));
                dcPage::addSuccessNotice(__('Logs successfully deleted'));
                dcCore::app()->adminurl?->redirect('admin.plugin.' . My::id());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        $logs   = $counter = $list = null;
        $filter = new adminGenericFilter(dcCore::app(), My::id());
        $params = new ArrayObject($filter->params());

        try {
            $logs    = ActivityReport::instance()->getLogs($params);
            $counter = ActivityReport::instance()->getLogs($params, true);
            if (!is_null($logs) && !is_null($counter)) {
                $list = new ManageList(dcCore::app(), $logs, $counter->f(0));
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        dcPage::openModule(
            My::name(),
            $filter->js((string) dcCore::app()->adminurl?->get('admin.plugin.' . My::id())) .
            dcPage::jsJson(My::id(), ['confirm_delete' => __('Are you sure you want to delete logs?')]) .
            dcPage::jsModuleLoad(My::id() . '/js/backend.js') .

            # --BEHAVIOR-- activityReportListHeader --
            dcCore::app()->callBehavior('activityReportListHeader')
        );

        echo
        dcPage::breadcrumb([
            __('Plugins') => '',
            My::name()    => '',
        ]) .
        dcPage::notices();

        if (!is_null($list)) {
            $filter->display('admin.plugin.' . My::id(), (new Hidden('p', My::id()))->render());
            $list->logsDisplay($filter, '%s');
        }

        if (!is_null($logs) && !$logs->isEmpty()) {
            echo
            (new Form('form-logs'))->method('post')->action(dcCore::app()->admin->getPageURL())->fields([
                (new Para())->class('right')->separator(' ')->items([
                    (new Submit('delete_all_logs'))->class('delete')->value(__('Delete all aticivity logs')),
                    (new Submit('delete_reported_logs'))->class('delete')->value(__('Delete all allready reported logs')),
                    dcCore::app()->formNonce(false),
                ]),
            ])->render();
        }

        dcPage::closeModule();
    }
}
