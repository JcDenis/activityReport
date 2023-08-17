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
use Dotclear\Core\Process;

/**
 * Front end process.
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

        dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), implode(DIRECTORY_SEPARATOR, [My::path(), 'default-templates', 'tpl']));

        dcCore::app()->tpl->addBlock('activityReports', [Template::class, 'activityReports']);
        dcCore::app()->tpl->addValue('activityReportFeedID', [Template::class, 'activityReportFeedID']);
        dcCore::app()->tpl->addValue('activityReportTitle', [Template::class, 'activityReportTitle']);
        dcCore::app()->tpl->addValue('activityReportDate', [Template::class, 'activityReportDate']);
        dcCore::app()->tpl->addValue('activityReportContent', [Template::class, 'activityReportContent']);

        return true;
    }
}
