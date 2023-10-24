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
        if (self::status()) {
            // be sure to init report
            ActivityReport::init();

            $tpl = App::frontend()->template();
            $tpl->setPath($tpl->getPath(), implode(DIRECTORY_SEPARATOR, [My::path(), 'default-templates', 'tpl']));
            $tpl->addBlock('activityReports', FrontendTemplate::activityReports(...));
            $tpl->addValue('activityReportFeedID', FrontendTemplate::activityReportFeedID(...));
            $tpl->addValue('activityReportTitle', FrontendTemplate::activityReportTitle(...));
            $tpl->addValue('activityReportDate', FrontendTemplate::activityReportDate(...));
            $tpl->addValue('activityReportContent', FrontendTemplate::activityReportContent(...));
        }

        return self::status();
    }
}
