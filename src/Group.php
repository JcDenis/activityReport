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
 * Actions stack.
 */
class Group
{
    /** @var    array<string, Action>   $stack  The actions stack */
    private array $stack = [];

    /**
     * Constructor sets group description.
     *
     * @param   string  $id     The group ID
     * @param   string  $title  The group title
     */
    public function __construct(public readonly string $id, public readonly string $title)
    {
    }

    /**
     * Chek if a action exists.
     *
     * @param   string  $id     The action ID
     *
     * @return  bool    True if it exists
     */
    public function has(string $id): bool
    {
        return isset($this->stack[$id]);
    }

    /**
     * Add an action.
     *
     * @param   Action  $action     The action object
     *
     * @return  Group   The group instance
     */
    public function add(Action $action): Group
    {
        $this->stack[$action->id] = $action;

        return $this;
    }

    /**
     * Get an action.
     *
     * @param   string  $id     The action ID
     *
     * @return  Action  The action descriptor
     */
    public function get(string $id): Action
    {
        return $this->stack[$id] ?? new Action($id, 'undefined', 'undefined', 'undefined', null);
    }

    /**
     * Get all actions.
     *
     * @return array<string, Action>    The actions stack
     */
    public function dump(): array
    {
        return $this->stack;
    }
}
