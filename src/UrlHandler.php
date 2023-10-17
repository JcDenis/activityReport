<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use Dotclear\App;
use Dotclear\Core\Frontend\Url;

/**
 * @brief       activityReport frontend URL handler class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class UrlHandler extends Url
{
    /**
     * Get activity logs feed.
     *
     * @param   null|string     $args   The URL arguments
     */
    public static function feed(?string $args): void
    {
        // no context or wrong URL args or module no loaded or report unactive
        if (!preg_match('/^(atom|rss2)\/(.+)$/', (string) $args, $m)
            || !defined('ACTIVITY_REPORT')
            || !ActivityReport::instance()->settings->feed_active
        ) {
            self::p404();
        }
        /*
                if (!is_array($m) || count($m) < 2 || !is_string($m[1]) || !is_string($m[2])) {
                    self::p404();
                }
        */
        // get type of feed
        $mime = $m[1] == 'atom' ? 'application/atom+xml' : 'application/xml';
        if (false === ActivityReport::instance()->checkUserCode($m[2])) {
            self::p404();
        }

        // feed limits
        $nb = App::blog()->settings()->get('system')->get('nb_post_per_feed');
        //$it = App::blog()->settings()->get('system')->get('short_feed_items');
        $rb = App::blog()->settings()->get('system')->get('robots_policy');

        App::frontend()->context()->__set('nb_entry_per_page', is_numeric($nb) ? (int) $nb : 20);
        // App::frontend->context()->__set('short_feed_items', is_numerci($it) ? (int) $it : 1);

        // serve activity feed template
        header('X-Robots-Tag: ' . App::frontend()->context()::robotsPolicy(is_string($rb) ? $rb : '', ''));
        self::serveDocument('activityreport-' . $m[1] . '.xml', $mime);
    }
}
