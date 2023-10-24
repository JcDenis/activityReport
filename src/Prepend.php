<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       activityReport prepend class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // regirster activity feed URL
        App::url()->register(
            My::id(),
            'reports',
            '^reports/((atom|rss2)/(.+))$',
            FrontendUrl::feed(...)
        );

        return true;
    }
}
