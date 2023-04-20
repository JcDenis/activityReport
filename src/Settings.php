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

use dcCore;
use Exception;

/**
 * Module settings helper.
 */
class Settings
{
    /** @var    bool    $feed_active    The activity feed activation */
    public readonly bool $feed_active;

    /** @var    int     $obsolete   The logs obsolete time */
    public readonly int $obsolete;

    /** @var    int     $interval   The report interval time */
    public readonly int $interval;

    /** @var    int     $lastreport     The last report time */
    public readonly int $lastreport;

    /** @var    array   $mailinglist    The mailing list */
    public readonly array $mailinglist;

    /** @var    string  $mailformat     The mail content format */
    public readonly string $mailformat;

    /** @var    string $date format     The date format */
    public readonly string $dateformat;

    /** @var    array   $requests   The selected actions list to report */
    public readonly array $requests;

    /**
     * Constructor sets settings properties.
     */
    public function __construct()
    {
        if (dcCore::app()->blog === null) {
            throw new Exception('Blog is not set');
        }

        $this->feed_active = (bool) ($this->get('feed_active') ?? false);
        $this->obsolete    = (int) ($this->get('obsolete') ?? 2419200);
        $this->interval    = (int) ($this->get('interval') ?? 86400);
        $this->lastreport  = (int) ($this->get('lastreport') ?? 0);
        $this->mailinglist = (array) ($this->get('mailinglist') ?? []);
        $this->mailformat  = (string) ($this->get('mailformat') ?? 'plain');
        $this->dateformat  = (string) ($this->get('dateformat') ?? '%Y-%m-%d %H:%M:%S');
        $this->requests    = (array) ($this->get('requests') ?? []);
    }

    /**
     * Dump properties.
     *
     * @return  array<string, mixed> The settings properties
     */
    public function dump(): array
    {
        $vars = get_class_vars(__CLASS__);

        return  $vars ? $vars : [];
    }

    /**
     * Set a setting in database.
     *
     * Setting is modified in database but not in script !
     *
     * @param   string  $key    The setting key
     * @param   mixed   $value  The setting value
     */
    public function set(string $key, mixed $value): void
    {
        if (property_exists($this, $key) && gettype($value) == gettype($this->{$key})) {
            dcCore::app()->blog?->settings->get(My::id())->put(
                $key,
                $value,
                gettype($value),
                null,
                true,
                false
            );
        }
    }

    /**
     * Get a setting from database.
     *
     * @param   string  $key    The setting key
     *
     * @return  mixed   The setting value
     */
    private function get(string $key): mixed
    {
        return dcCore::app()->blog?->settings->get(My::id())->get($key);
    }
}
