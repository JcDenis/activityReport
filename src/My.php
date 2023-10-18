<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use Dotclear\Module\MyPlugin;

/**
 * @brief       activityReport My helper.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    /**
     * Activity database table name.
     *
     * @var     string   ACTIVITY_TABLE_NAME
     */
    public const ACTIVITY_TABLE_NAME = 'activity';

    // Use default permissions
}
