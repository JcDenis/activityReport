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
 * Actions groups stack.
 */
class Groups
{
    /** @var    array<string, Group>    $stack  The actions groups stack */
    private array $stack = [];

    /**
     * Chek if a group exists.
     *
     * @param   string  $id     The group ID
     *
     * @return  bool    True if it exists
     */
    public function has(string $id): bool
    {
        return isset($this->stack[$id]);
    }

    /**
     * Add a group.
     *
     * @param   Group  $group   The group object
     *
     * @return  Groups The groups instance
     */
    public function add(Group $group): Groups
    {
        $this->stack[$group->id] = $group;

        return $this;
    }

    /**
     * Get a group.
     *
     * @param   string  $id     The group ID
     *
     * @return  Group  The group descriptor
     */
    public function get(string $id): Group
    {
        return $this->stack[$id] ?? new Group($id, 'undefined');
    }

    /**
     * Get all groups.
     *
     * @return array<string, Group>    The groups stack
     */
    public function dump(): array
    {
        return $this->stack;
    }
}
