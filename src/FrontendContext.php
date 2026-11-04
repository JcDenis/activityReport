<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use Dotclear\App;
use Dotclear\Database\MetaRecord;

/**
 * @brief       activityReport frontend context class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FrontendContext
{
    /**
     * Parse title.
     *
     * @return  string  The parsed title
     */
    public static function parseTitle(): string
    {
        if (!App::frontend()->context()->exists('activityreports')
            || !(App::frontend()->context()->__get('activityreports') instanceof MetaRecord)
        ) {
            return '';
        }

        $group  = App::frontend()->context()->__get('activityreports')->strField('activity_group');
        $action = App::frontend()->context()->__get('activityreports')->strField('activity_action');

        if (!ActivityReport::instance()->groups->get($group)->has($action)) {
            return '';
        }

        return __(ActivityReport::instance()->groups->get($group)->get($action)->title);
    }

    /**
     * Parse content.
     *
     * @return  string  The parsed content
     */
    public static function parseContent(): string
    {
        if (!App::frontend()->context()->exists('activityreports')
            || !(App::frontend()->context()->__get('activityreports') instanceof MetaRecord)
        ) {
            return '';
        }

        $group  = App::frontend()->context()->__get('activityreports')->strField('activity_group');
        $action = App::frontend()->context()->__get('activityreports')->strField('activity_action');
        $logs   = App::frontend()->context()->__get('activityreports')->strField('activity_logs');
        $logs   = json_decode($logs, true);

        if (!is_array($logs)
            || !ActivityReport::instance()->groups->has($group)
            || !ActivityReport::instance()->groups->get($group)->has($action)
        ) {
            return '';
        }

        App::filter()->initWikiComment();

        return App::filter()->wikiTransform(ActivityReport::parseMessage(
            __(ActivityReport::instance()->groups->get($group)->get($action)->message),
            array_values(array_map(fn ($v): string => is_scalar($v) ? (string) $v : '', $logs))
        ));
    }
}
