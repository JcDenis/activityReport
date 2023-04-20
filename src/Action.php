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

/**
 * Action descriptor.
 */
class Action
{
    /**
     * Constructor sets action description.
     *
     * @param   string  $id     The action ID
     * @param   string  $title  The action title
     * @param   string  $message    The action message
     * @param   string  $behavior   The behavior name
     * @param   null|callable   $function   The callback function
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $title,
        public readonly string  $message,
        string  $behavior,
        ?callable $function
    ) {
        // fake action has no behavior
        if (!is_null($function)) {
            dcCore::app()->addBehavior($behavior, $function);
        }
    }
}
