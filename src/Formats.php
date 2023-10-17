<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

/**
 * @brief       activityReport formats stack.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Formats
{
    /** @var    array<string, Format>  $stack   The formats stack */
    private array $stack = [];

    /**
     * Chek if a format exists.
     *
     * @param   string  $id     The format ID
     *
     * @return  bool    True if it exists
     */
    public function has(string $id): bool
    {
        return isset($this->stack[$id]);
    }

    /**
     * Add a format.
     *
     * @param   Format  $format     The format object
     *
     * @return  Formats The formats instance
     */
    public function add(Format $format): Formats
    {
        $this->stack[$format->id] = $format;

        return $this;
    }

    /**
     * Get a format.
     *
     * @param   string  $id     The format ID
     *
     * @return  Format  The format descriptor
     */
    public function get(string $id): Format
    {
        return $this->stack[$id] ?? new Format('plain', []);
    }

    /**
     * Get all formats.
     *
     * @return array<string, Format>    The formats stack
     */
    public function dump(): array
    {
        return $this->stack;
    }
}
