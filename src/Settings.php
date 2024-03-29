<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

/**
 * @brief       activityReport settings helper class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
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

    /** @var    array<int,string>   $mailinglist    The mailing list */
    public readonly array $mailinglist;

    /** @var    string  $mailformat     The mail content format */
    public readonly string $mailformat;

    /** @var    string $date format     The date format */
    public readonly string $dateformat;

    /** @var    array<string,array{string,int}>   $requests   The selected actions list to report */
    public readonly array $requests;

    /**
     * Constructor sets settings properties.
     */
    public function __construct()
    {
        $this->feed_active = (bool) ($this->get('feed_active') ?? false);
        $this->obsolete    = is_numeric($this->get('obsolete')) ? (int) $this->get('obsolete') : 2419200;
        $this->interval    = is_numeric($this->get('interval')) ? (int) $this->get('interval') : 86400;
        $this->lastreport  = is_numeric($this->get('lastreport')) ? (int) $this->get('lastreport') : 0;
        $this->mailinglist = is_array($this->get('mailinglist')) ? $this->get('mailinglist') : [];
        $this->mailformat  = is_string($this->get('mailformat')) ? $this->get('mailformat') : 'plain';
        $this->dateformat  = is_string($this->get('dateformat')) ? $this->get('dateformat') : '%Y-%m-%d %H:%M:%S';
        $this->requests    = is_array($this->get('requests')) ? $this->get('requests') : [];
    }

    /**
     * Dump properties.
     *
     * @return  array<string, mixed> The settings properties
     */
    public function dump(): array
    {
        return get_object_vars($this);
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
            My::settings()->put(
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
        return My::settings()->get($key);
    }
}
