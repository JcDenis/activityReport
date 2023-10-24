<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       activityReport backend class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (self::status()) {
            // be sure to init report
            ActivityReport::init();

            My::addBackendMenuItem();

            App::behavior()->addBehaviors([
                'adminDashboardFavoritesV2'        => BackendBehaviors::adminDashboardFavoritesV2(...),
                'adminDashboardContentsV2'         => BackendBehaviors::adminDashboardContentsV2(...),
                'adminDashboardOptionsFormV2'      => BackendBehaviors::adminDashboardOptionsFormV2(...),
                'adminAfterDashboardOptionsUpdate' => BackendBehaviors::adminAfterDashboardOptionsUpdate(...),
                'adminFiltersListsV2'              => BackendBehaviors::adminFiltersListsV2(...),
                'adminColumnsListsV2'              => BackendBehaviors::adminColumnsListsV2(...),
            ]);
        }

        return self::status();
    }
}
