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

if (!defined('DC_RC_PATH')){return;}

if (!defined('ACTIVITY_REPORT')){return;}

$core->tpl->setPath($core->tpl->getPath(),dirname(__FILE__).'/default-templates/tpl');
$core->tpl->addBlock('activityReports',array('activityReportPublicTpl','activityReports'));
$core->tpl->addValue('activityReportFeedID',array('activityReportPublicTpl','activityReportFeedID'));
$core->tpl->addValue('activityReportTitle',array('activityReportPublicTpl','activityReportTitle'));
$core->tpl->addValue('activityReportDate',array('activityReportPublicTpl','activityReportDate'));
$core->tpl->addValue('activityReportContent',array('activityReportPublicTpl','activityReportContent'));

class activityReportPublicUrl extends dcUrlHandlers
{
    public static function feed($args)
    {
        global $core, $_ctx;

        if (!preg_match('/^(atom|rss2)\/(.+)$/',$args,$m))
        {
            self::p404();
            return;
        }
        if (!defined('ACTIVITY_REPORT')){
            self::p404();
            return;
        }
        if (!$core->activityReport->getSetting('active'))
        {
            self::p404();
            return;
        }
        $mime = $m[1] == 'atom' ? 'application/atom+xml' : 'application/xml';

        if (false === $core->activityReport->checkUserCode($m[2])) {
            self::p404();
            return;
        }

        $_ctx->nb_entry_per_page = $core->blog->settings->system->nb_post_per_feed;
        $_ctx->short_feed_items = $core->blog->settings->system->short_feed_items;

        header('X-Robots-Tag: '.context::robotsPolicy($core->blog->settings->system->robots_policy,''));
        self::serveDocument('activityreport-'.$m[1].'.xml',$mime);
        return;
    }
}

class activityReportPublicTpl
{
    public static function activityReports($attr,$content)
    {
        $lastn = 0;
        if (isset($attr['lastn'])) {
            $lastn = abs((integer) $attr['lastn'])+0;
        }

        $p = 'if (!isset($_page_number)) { $_page_number = 1; }'."\n\$params = array();\n";

        if ($lastn > 0) {
            $p .= "\$params['limit'] = ".$lastn.";\n";
        } else {
            $p .= "\$params['limit'] = \$_ctx->nb_entry_per_page;\n";
        }

        if (!isset($attr['ignore_pagination']) || $attr['ignore_pagination'] == "0") {
            $p .= "\$params['limit'] = array(((\$_page_number-1)*\$params['limit']),\$params['limit']);\n";
        } else {
            $p .= "\$params['limit'] = array(0, \$params['limit']);\n";
        }

        $res = 
        "<?php \n".
        $p.
        '$_ctx->activityreport_params = $params; '."\n".
        '$_ctx->activityreports = $core->activityReport->getLogs($params); unset($params); '."\n".
        'while ($_ctx->activityreports->fetch()) : ?>'.$content.'<?php endwhile; '.
        '$_ctx->activityreports = null; $_ctx->activityreport_params = null; '."\n".
        "?>";

        return $res;
    }

    public static function activityReportFeedID($attr)
    {
        return 
        'urn:md5:<?php echo md5($_ctx->activityreports->blog_id.'.
        '$_ctx->activityreports->activity_id.$_ctx->activityreports->activity_dt); '.
        '?>';
    }

    public static function activityReportTitle($attr)
    {
        $f = $GLOBALS['core']->tpl->getFilters($attr);
        return '<?php echo '.sprintf($f,'activityReportContext::parseTitle()').'; ?>';
    }

    public static function activityReportContent($attr)
    {
        $f = $GLOBALS['core']->tpl->getFilters($attr);
        return '<?php echo '.sprintf($f,'activityReportContext::parseContent()').'; ?>';
    }

    public static function activityReportDate($attr)
    {
        $format = '';
        if (!empty($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $iso8601 = !empty($attr['iso8601']);
        $rfc822 = !empty($attr['rfc822']);

        $f = $GLOBALS['core']->tpl->getFilters($attr);

        if ($rfc822) {
            return '<?php echo '.sprintf($f,"dt::rfc822(strtotime(\$_ctx->activityreports->activity_dt),\$core->blog->settings->system->blog_timezone)").'; ?>';
        } elseif ($iso8601) {
            return '<?php echo '.sprintf($f,"dt::iso8601(strtotime(\$_ctx->activityreports->activity_dt),\$core->blog->settings->system->blog_timezone)").'; ?>';
        } elseif (!empty($format)) {
            return '<?php echo '.sprintf($f,"dt::dt2str('".$format."',\$_ctx->activityreports->activity_dt)").'; ?>';
        } else {
            return '<?php echo '.sprintf($f,"dt::dt2str(\$core->blog->settings->system->date_format,\$_ctx->activityreports->activity_dt)").'; ?>';
        }
    }
}

class activityReportContext
{
    public static function parseTitle()
    {
        global $core,$_ctx;

        $groups = $core->activityReport->getGroups();

        $group = $_ctx->activityreports->activity_group;
        $action = $_ctx->activityreports->activity_action;

        if (!empty($groups[$group]['actions'][$action]['title'])) {
            return __($groups[$group]['actions'][$action]['title']);
        }
        return '';
    }

    public static function parseContent()
    {
        global $core,$_ctx;

        $groups = $core->activityReport->getGroups();

        $group = $_ctx->activityreports->activity_group;
        $action = $_ctx->activityreports->activity_action;
        $logs = $_ctx->activityreports->activity_logs;
        $logs = $core->activityReport->decode($logs);

        if (!empty($groups[$group]['actions'][$action]['msg'])) {
            $core->initWikiComment();
            return $core->wikiTransform(vsprintf(__($groups[$group]['actions'][$action]['msg']),$logs));
        }
        return '';
    }
}