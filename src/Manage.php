<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Filter\Filters;
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Form,
    Hidden,
    Para,
    Submit,
    Text
};
use Exception;

/**
 * @brief       activityReport manage class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!empty($_POST['delete_all_logs']) || !empty($_POST['delete_reported_logs'])) {
            try {
                ActivityReport::instance()->deleteLogs(!empty($_POST['delete_reported_logs']));
                Notices::addSuccessNotice(__('Logs successfully deleted'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $logs   = $counter = $list = null;
        $filter = new Filters(My::id());
        $params = new ArrayObject($filter->params());

        try {
            $logs    = ActivityReport::instance()->getLogs($params);
            $counter = ActivityReport::instance()->getLogs($params, true);
            if (!is_null($logs) && !is_null($counter)) {
                $list = new ManageList($logs, $counter->f(0));
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        Page::openModule(
            My::name(),
            $filter->js((string) My::manageUrl()) .
            Page::jsJson(My::id(), ['confirm_delete' => __('Are you sure you want to delete logs?')]) .
            My::jsLoad('backend') .

            # --BEHAVIOR-- activityReportListHeader --
            App::behavior()->callBehavior('activityReportListHeader')
        );

        echo
        Page::breadcrumb([
            __('Plugins') => '',
            My::name()    => '',
        ]) .
        Notices::getNotices();

        if (!is_null($list)) {
            $filter->display('admin.plugin.' . My::id(), (new Hidden('p', My::id()))->render());
            $list->logsDisplay($filter, '%s');
        }

        if (!is_null($logs) && !$logs->isEmpty()) {
            echo
            (new Form('form-logs'))->method('post')->action(App::backend()->getPageURL())->fields([
                (new Para())->class('right')->separator(' ')->items([
                    (new Submit('delete_all_logs'))->class('delete')->value(__('Delete all aticivity logs')),
                    (new Submit('delete_reported_logs'))->class('delete')->value(__('Delete all allready reported logs')),
                    ... My::hiddenFields(),
                ]),
            ])->render();
        }

        Page::closeModule();
    }
}
