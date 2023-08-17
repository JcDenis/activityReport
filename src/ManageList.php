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
use dcCore;
use Dotclear\Core\Backend\Filter\Filters;
use Dotclear\Core\Backend\Listing\{
    Listing,
    Pager
};
use Dotclear\Helper\Date;

/**
 * Logs admin list helper.
 */
class ManageList extends Listing
{
    public function logsDisplay(Filters $filter, string $enclose_block = ''): void
    {
        if (!$this->rs || $this->rs->isEmpty()) {
            if ($filter->show()) {
                echo '<p><strong>' . __('No log matches the filter') . '</strong></p>';
            } else {
                echo '<p><strong>' . __('No log') . '</strong></p>';
            }
        } else {
            $page            = $filter->value('page');
            $nbpp            = $filter->value('nb');
            $pager           = new Pager(is_numeric($page) ? (int) $page : 1, (int) $this->rs_count, is_numeric($nbpp) ? (int) $nbpp : 20, 10);
            $pager->var_page = 'page';

            $html_block = '<div class="table-outer"><table><caption>' . (
                $filter->show() ?
                sprintf(__('List of %s logs matching the filter.'), $this->rs_count) :
                sprintf(__('List of %s logs.'), $this->rs_count)
            ) . '</caption>';

            $cols = new ArrayObject([
                'activity_group'  => '<th scope="col" class="nowrap">' . __('Group') . '</th>',
                'activity_action' => '<th scope="col" class="nowrap">' . __('Action') . '</th>',
                'activity_logs'   => '<th scope="col" class="nowrap">' . __('Message') . '</th>',
                'activity_date'   => '<th scope="col" class="nowrap">' . __('Date') . '</th>',
                'activity_status' => '<th scope="col" class="nowrap">' . __('Status') . '</th>',
            ]);

            $this->userColumns(My::id(), $cols);

            $html_block .= '<tr>' . implode(iterator_to_array($cols)) . '</tr>%s</table>%s</div>';
            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }
            $blocks = explode('%s', $html_block);

            echo $pager->getLinks() . $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->logsLine();
            }

            echo $blocks[1] . $blocks[2] . $pager->getLinks();
        }
    }

    private function logsLine(): string
    {
        $row = new ActivityRow($this->rs);

        $offline = $row->status == ActivityReport::STATUS_REPORTED ? ' offline' : '';
        $group   = ActivityReport::instance()->groups->get($row->group);
        $action  = $group->get($row->action);
        $message = ActivityReport::parseMessage(__($action->message), $row->logs);
        $date    = Date::str(
            dcCore::app()->blog?->settings->get('system')->get('date_format') . ', ' . dcCore::app()->blog?->settings->get('system')->get('time_format'),
            (int) strtotime($row->dt),
            is_string(dcCore::app()->auth->getInfo('user_tz')) ? dcCore::app()->auth->getInfo('user_tz') : 'UTC'
        );
        $status = $row->status == ActivityReport::STATUS_PENDING ? __('pending') : __('reported');

        $cols = new ArrayObject([
            'activity_group'  => '<td class="nowrap">' . __($group->title) . '</td>',
            'activity_action' => '<td class="nowrap">' . __($action->title) . '</td>',
            'activity_logs'   => '<td class="maximal">' . $message . '</td>',
            'activity_date'   => '<td class="nowrap">' . $date . '</td>',
            'activity_status' => '<td class="nowrap">' . $status . '</td>',
        ]);

        $this->userColumns(My::id(), $cols);

        return
            '<tr class="line ' . $offline . '" id="l' . $row->id . '">' .
            implode(iterator_to_array($cols)) .
            '</tr>';
    }
}
