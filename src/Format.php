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

/**
 * Report format descriptor.
 */
class Format
{
    /** @var    string    $name     The name format */
    public readonly string  $name;

    /** @var    string    $blog_title   The blog title format */
    public readonly string  $blog_title;

    /** @var    string    $group_title  The group title format */
    public readonly string  $group_title;

    /** @var    string    $group_open   The group opening format */
    public readonly string  $group_open;

    /** @var    string    $group_close  The group closing format */
    public readonly string  $group_close;

    /** @var    string    $action   The action format */
    public readonly string  $action;

    /** @var    string    $error    The error format */
    public readonly string  $error;

    /** @var    string    $period_title     The period title format */
    public readonly string  $period_title;

    /** @var    string    $period_open   The period opening format */
    public readonly string  $period_open;

    /** @var    string    $period_close     The period closing format */
    public readonly string  $period_close;

    /** @var    string    $info     The info format */
    public readonly string  $info;

    /** @var    string    $page     The page format */
    public readonly string  $page;

    /**
     * Constructor sets format id.
     *
     * @param   string  $id     The format ID
     * @param   array   $format     The format values
     */
    public function __construct(
        public readonly string  $id,
        array $format
    ) {
        $this->name         = $format['name']         ?? __('Plain text');
        $this->blog_title   = $format['blog_title']   ?? "\n--- %TEXT% ---\n";
        $this->group_title  = $format['group_title']  ?? "\n-- %TEXT% --\n\n";
        $this->group_open   = $format['group_open']   ?? '';
        $this->group_close  = $format['group_close']  ?? '';
        $this->action       = $format['action']       ?? "- %TIME% : %TEXT%\n";
        $this->error        = $format['error']        ?? '%TEXT%';
        $this->period_title = $format['period_title'] ?? "%TEXT%\n";
        $this->period_open  = $format['period_open']  ?? '';
        $this->period_close = $format['period_close'] ?? '';
        $this->info         = $format['info']         ?? "%TEXT%\n";
        $this->page         = $format['page']         ?? "%PERIOD%\n-----------------------------------------------------------\n%TEXT%";
    }
}
