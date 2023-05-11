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

use context;
use dcCore;
use dcUrlHandlers;

/**
 * Frontend URL handler.
 */
class UrlHandler extends dcUrlHandlers
{
    /**
     * Get activity logs feed.
     *
     * @param   null|string     $args   The URL arguments
     */
    public static function feed(?string $args): void
    {
        // no context or wrong URL args or module no loaded or report unactive
        if (!dcCore::app()->ctx
            || !preg_match('/^(atom|rss2)\/(.+)$/', (string) $args, $m)
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
        $nb = dcCore::app()->blog?->settings->get('system')->get('nb_post_per_feed');
        //$it = dcCore::app()->blog?->settings->get('system')->get('short_feed_items');
        $rb = dcCore::app()->blog?->settings->get('system')->get('robots_policy');

        dcCore::app()->ctx->__set('nb_entry_per_page', is_numeric($nb) ? (int) $nb : 20);
        // dcCore::app()->ctx->__set('short_feed_items', is_numerci($it) ? (int) $it : 1);

        // serve activity feed template
        header('X-Robots-Tag: ' . context::robotsPolicy(is_string($rb) ? $rb : '', ''));
        self::serveDocument('activityreport-' . $m[1] . '.xml', $mime);
    }
}
