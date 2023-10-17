<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       activityReport frontend class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::frontend()->template()->setPath(App::frontend()->template()->getPath(), implode(DIRECTORY_SEPARATOR, [My::path(), 'default-templates', 'tpl']));

        App::frontend()->template()->addBlock('activityReports', Template::activityReports(...));
        App::frontend()->template()->addValue('activityReportFeedID', Template::activityReportFeedID(...));
        App::frontend()->template()->addValue('activityReportTitle', Template::activityReportTitle(...));
        App::frontend()->template()->addValue('activityReportDate', Template::activityReportDate(...));
        App::frontend()->template()l->addValue('activityReportContent', Template::activityReportContent(...));

        return true;
    }
}
