<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use Dotclear\Database\MetaRecord;

/**
 * @brief       activityReport activity record row descriptor class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ActivityRow
{
    /** @var    int     $id     The activity ID */
    public readonly int $id;

    /** @var    string  $id     The activity blog ID */
    public readonly ?string $blog_id;

    /** @var    string  $id     The activity blog URL */
    public readonly string $blog_url;

    /** @var    string  $id     The activity blog name */
    public readonly string $blog_name;

    /** @var    string  $id     The activity group */
    public readonly string $group;

    /** @var    string  $id     The activity action */
    public readonly string $action;

    /** @var    string  $id     The activity date */
    public readonly string $dt;

    /** @var    int     $id     The activity status */
    public readonly int $status;

    /** @var    array<int,string>   The log data */
    public readonly array $logs;

    /**
     * Constructor sets properties.
     *
     * @param   MetaRecord  $rs     The record
     */
    public function __construct(
        public readonly MetaRecord $rs
    ) {
        $this->id        = $this->rs->intField('activity_id');
        $this->blog_id   = $this->rs->strField('blog_id');
        $this->blog_url  = $this->rs->strField('blog_url');
        $this->blog_name = $this->rs->strField('blog_name');
        $this->group     = $this->rs->strField('activity_group');
        $this->action    = $this->rs->strField('activity_action');
        $this->dt        = $this->rs->strField('activity_dt');
        $this->status    = $this->rs->intField('activity_status');

        $logs       = json_decode($this->rs->strField('activity_logs'), true);
        $this->logs = is_array($logs) ? $logs : [];
    }
}
