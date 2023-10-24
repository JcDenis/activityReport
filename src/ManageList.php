<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Filter\Filters;
use Dotclear\Core\Backend\Listing\{
    Listing,
    Pager
};
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\{
    Caption,
    Div,
    Note,
    Table,
    Tbody,
    Td,
    Text,
    Th,
    Tr
};
use Dotclear\Helper\Html\Html;

/**
 * @brief       activityReport manage logs list class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ManageList extends Listing
{
    public function logsDisplay(Filters $filter, string $enclose_block = ''): void
    {
        if ($this->rs->isEmpty()) {
            echo (new Note())
                ->text($filter->show() ? __('No log matches the filter') : __('No log'))
                ->class('info')
                ->render();

            return;
        }

        $page            = is_numeric($filter->value('page')) ? (int) $filter->value('page') : 1;
        $nbpp            = is_numeric($filter->value('nb')) ? (int) $filter->value('nb') : 20;
        $count           = (int) $this->rs_count;
        $pager           = new Pager($page, $count, $nbpp, 10);
        $pager->var_page = 'page';

        $cols = new ArrayObject([
            'activity_group' => (new Th())
                ->text(__('Group'))
                ->scope('col'),
            'activity_action' => (new Th())
                ->text(__('Action'))
                ->scope('col'),
            'activity_logs' => (new Th())
                ->text(__('Message'))
                ->scope('col'),
            'activity_date' => (new Th())
                ->text(__('Date'))
                ->scope('col'),
            'activity_status' => (new Th())
                ->text(__('Status'))
                ->scope('col'),
        ]);

        $this->userColumns(My::id(), $cols);

        $lines = [];
        while ($this->rs->fetch()) {
            $lines[] = $this->line();
        }

        echo
        $pager->getLinks() .
        sprintf(
            $enclose_block,
            (new Div())
                ->class('table-outer')
                ->items([
                    (new Table())
                        ->items([
                            (new Caption(
                                $filter->show() ?
                                sprintf(__('List of %s logs matching the filter.'), $count) :
                                sprintf(__('List of logs. (%s)'), $count)
                            )),
                            (new Tr())
                                ->items(iterator_to_array($cols)),
                            (new Tbody())
                                ->items($lines),
                        ]),
                ])
                ->render()
        ) .
        $pager->getLinks();
    }

    private function line(): Tr
    {
        $row = new ActivityRow($this->rs);

        $offline = $row->status == ActivityReport::STATUS_REPORTED ? ' offline' : '';
        $group   = ActivityReport::instance()->groups->get($row->group);
        $action  = $group->get($row->action);
        $message = ActivityReport::parseMessage(__($action->message), $row->logs);
        $date    = Date::str(
            App::blog()->settings()->get('system')->get('date_format') . ', ' . App::blog()->settings()->get('system')->get('time_format'),
            (int) strtotime($row->dt),
            is_string(App::auth()->getInfo('user_tz')) ? App::auth()->getInfo('user_tz') : 'UTC'
        );
        $status = $row->status == ActivityReport::STATUS_PENDING ? __('pending') : __('reported');

        $cols = new ArrayObject([
            'activity_group' => (new Td())
                ->text(Html::escapeHTML(__($group->title)))
                ->class('nowrap'),
            'activity_action' => (new Td())
                ->text(Html::escapeHTML(__($action->title)))
                ->class('nowrap'),
            'activity_logs' => (new Td())
                ->text(Html::escapeHTML($message))
                ->class('maximal'),
            'activity_date' => (new Td())
                ->text(Html::escapeHTML($date))
                ->class('nowrap'),
            'activity_status' => (new Td())
                ->text(Html::escapeHTML($status))
                ->class('nowrap'),
        ]);

        $this->userColumns(My::id(), $cols);

        return (new Tr('l' . $row->id))
            ->class('line' . $offline)
            ->items(iterator_to_array($cols));
    }
}
