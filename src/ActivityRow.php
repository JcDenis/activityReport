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
        $this->id        = is_numeric($this->rs->f('activity_id')) ? (int) $this->rs->f('activity_id') : 0;
        $this->blog_id   = is_string($this->rs->f('blog_id')) ? $this->rs->f('blog_id') : null;
        $this->blog_url  = is_string($this->rs->f('blog_url')) ? $this->rs->f('blog_url') : '';
        $this->blog_name = is_string($this->rs->f('blog_name')) ? $this->rs->f('blog_name') : '';
        $this->group     = is_string($this->rs->f('activity_group')) ? $this->rs->f('activity_group') : '';
        $this->action    = is_string($this->rs->f('activity_action')) ? $this->rs->f('activity_action') : '';
        $this->dt        = is_string($this->rs->f('activity_dt')) ? $this->rs->f('activity_dt') : '';
        $this->status    = is_numeric($this->rs->f('activity_status')) ? (int) $this->rs->f('activity_status') : 0;

        $logs       = json_decode(is_string($this->rs->f('activity_logs')) ? $this->rs->f('activity_logs') : '', true);
        $this->logs = is_array($logs) ? $logs : [];
    }
}
