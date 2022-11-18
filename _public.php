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
if (!defined('DC_RC_PATH')) {
    return null;
}
if (!defined('ACTIVITY_REPORT_V2')) {
    return null;
}

dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/default-templates/tpl');
dcCore::app()->tpl->addBlock('activityReports', ['activityReportPublicTpl','activityReports']);
dcCore::app()->tpl->addValue('activityReportFeedID', ['activityReportPublicTpl','activityReportFeedID']);
dcCore::app()->tpl->addValue('activityReportTitle', ['activityReportPublicTpl','activityReportTitle']);
dcCore::app()->tpl->addValue('activityReportDate', ['activityReportPublicTpl','activityReportDate']);
dcCore::app()->tpl->addValue('activityReportContent', ['activityReportPublicTpl','activityReportContent']);

class activityReportPublicUrl extends dcUrlHandlers
{
    public static function feed(?string $args): void
    {
        if (!preg_match('/^(atom|rss2)\/(.+)$/', $args, $m)) {
            self::p404();

            return;
        }
        if (!defined('ACTIVITY_REPORT_V2')) {
            self::p404();

            return;
        }
        if (!dcCore::app()->activityReport->getSetting('active')) {
            self::p404();

            return;
        }
        $mime = $m[1] == 'atom' ? 'application/atom+xml' : 'application/xml';

        if (false === dcCore::app()->activityReport->checkUserCode($m[2])) {
            self::p404();

            return;
        }

        dcCore::app()->ctx->nb_entry_per_page = (int) dcCore::app()->blog->settings->system->nb_post_per_feed;
        dcCore::app()->ctx->short_feed_items  = (int) dcCore::app()->blog->settings->system->short_feed_items;

        header('X-Robots-Tag: ' . context::robotsPolicy(dcCore::app()->blog->settings->system->robots_policy, ''));
        self::serveDocument('activityreport-' . $m[1] . '.xml', $mime);
    }
}

class activityReportPublicTpl
{
    public static function activityReports($attr, $content)
    {
        $lastn = 0;
        if (isset($attr['lastn'])) {
            $lastn = abs((int) $attr['lastn']) + 0;
        }

        $p = '$_page_number = dcCore::app()->public->getPageNumber(); if ($_page_number < 1) { $_page_number = 1; }' . "\n\$params = array();\n";

        if ($lastn > 0) {
            $p .= "\$params['limit'] = " . $lastn . ";\n";
        } else {
            $p .= "\$params['limit'] = dcCore::app()->ctx->nb_entry_per_page;\n";
        }

        if (!isset($attr['ignore_pagination']) || $attr['ignore_pagination'] == '0') {
            $p .= "\$params['limit'] = array(((\$_page_number-1)*\$params['limit']),\$params['limit']);\n";
        } else {
            $p .= "\$params['limit'] = array(0, \$params['limit']);\n";
        }

        return
        "<?php \n" .
        $p .
        'dcCore::app()->ctx->activityreport_params = $params; ' . "\n" .
        'dcCore::app()->ctx->activityreports = dcCore::app()->activityReport->getLogs($params); unset($params); ' . "\n" .
        'while (dcCore::app()->ctx->activityreports->fetch()) : ?>' . $content . '<?php endwhile; ' .
        'dcCore::app()->ctx->pop("activityreports"); dcCore::app()->ctx->pop("activityreport_params"); ' . "\n" .
        '?>';
    }

    public static function activityReportFeedID($attr)
    {
        return
        'urn:md5:<?php echo md5(dcCore::app()->ctx->activityreports->blog_id.' .
        'dcCore::app()->ctx->activityreports->activity_id.dcCore::app()->ctx->activityreports->activity_dt); ' .
        '?>';
    }

    public static function activityReportTitle($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'activityReportContext::parseTitle()') . '; ?>';
    }

    public static function activityReportContent($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'activityReportContext::parseContent()') . '; ?>';
    }

    public static function activityReportDate($attr)
    {
        $format = '';
        if (!empty($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $iso8601 = !empty($attr['iso8601']);
        $rfc822  = !empty($attr['rfc822']);

        $f = dcCore::app()->tpl->getFilters($attr);

        if ($rfc822) {
            return '<?php echo ' . sprintf($f, 'dt::rfc822(strtotime(dcCore::app()->ctx->activityreports->activity_dt),dcCore::app()->blog->settings->system->blog_timezone)') . '; ?>';
        } elseif ($iso8601) {
            return '<?php echo ' . sprintf($f, 'dt::iso8601(strtotime(dcCore::app()->ctx->activityreports->activity_dt),dcCore::app()->blog->settings->system->blog_timezone)') . '; ?>';
        } elseif (!empty($format)) {
            return '<?php echo ' . sprintf($f, "dt::dt2str('" . $format . "',dcCore::app()->ctx->activityreports->activity_dt)") . '; ?>';
        }

        return '<?php echo ' . sprintf($f, 'dt::dt2str(dcCore::app()->blog->settings->system->date_format,dcCore::app()->ctx->activityreports->activity_dt)') . '; ?>';
    }
}

class activityReportContext
{
    public static function parseTitle()
    {
        $groups = dcCore::app()->activityReport->getGroups();

        $group  = dcCore::app()->ctx->activityreports->activity_group;
        $action = dcCore::app()->ctx->activityreports->activity_action;

        if (!empty($groups[$group]['actions'][$action]['title'])) {
            return __($groups[$group]['actions'][$action]['title']);
        }

        return '';
    }

    public static function parseContent()
    {
        $groups = dcCore::app()->activityReport->getGroups();

        $group  = dcCore::app()->ctx->activityreports->activity_group;
        $action = dcCore::app()->ctx->activityreports->activity_action;
        $logs   = dcCore::app()->ctx->activityreports->activity_logs;
        $logs   = dcCore::app()->activityReport->decode($logs);

        if (!empty($groups[$group]['actions'][$action]['msg'])) {
            dcCore::app()->initWikiComment();

            return dcCore::app()->wikiTransform(vsprintf(__($groups[$group]['actions'][$action]['msg']), $logs));
        }

        return '';
    }
}
