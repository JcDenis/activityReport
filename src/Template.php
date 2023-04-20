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

use ArrayObject;
use dcCore;
use Dotclear\Helper\Date;

/**
 * Template blocs and values.
 */
class Template
{
    /**
     * tpl:activityReports [attributes] : Activity report logs (tpl block)
     *
     * attributes:
     *
     *      - lastn                 integer     Limit to last n logs
     *      - ingnore_pagination    1|0         Ignore pagination paramaters
     *
     * @param   ArrayObject     $attr       The attributes
     * @param   string          $content    The content
     *
     * @return     string   The code
     */
    public static function activityReports(ArrayObject $attr, string $content): string
    {
        $lastn = 0;
        if (isset($attr['lastn'])) {
            $lastn = abs((int) $attr['lastn']) + 0;
        }

        $p = '$_page_number = dcCore::app()->public->getPageNumber(); if ($_page_number < 1) { $_page_number = 1; }' . "\n\$params = new ArrayObject();\n";

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
        'dcCore::app()->ctx->activityreports = ' . ActivityReport::class . '::instance()->getLogs($params); unset($params); ' . "\n" .
        'while (dcCore::app()->ctx->activityreports->fetch()) : ?>' . $content . '<?php endwhile; ' .
        'dcCore::app()->ctx->pop("activityreports"); dcCore::app()->ctx->pop("activityreport_params"); ' . "\n" .
        '?>';
    }

    /**
     * tpl:activityReportFeedID [attributes] : Activity report feed ID (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string   The code
     */
    public static function activityReportFeedID(ArrayObject $attr): string
    {
        return
        'urn:md5:<?php echo md5(dcCore::app()->ctx->activityreports->blog_id.' .
        'dcCore::app()->ctx->activityreports->activity_id.dcCore::app()->ctx->activityreports->activity_dt); ' .
        '?>';
    }

    /**
     * tpl:activityReportTitle [attributes] : Activity report log title (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string   The code
     */
    public static function activityReportTitle(ArrayObject $attr): string
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, Context::class . '::parseTitle()') . '; ?>';
    }

    /**
     * tpl:activityReportContent [attributes] : Activity report log message (tpl value)
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string   The code
     */
    public static function activityReportContent(ArrayObject $attr): string
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, Context::class . '::parseContent()') . '; ?>';
    }

    /**
     * tpl:activityReportDate [attributes] : Activity report log date (tpl value)
     *
     * attributes:
     *
     *      - format                  Use Date::str() (if iso8601 nor rfc822 were specified default to %Y-%m-%d %H:%M:%S)
     *      - iso8601         (1|0)   Use Date::iso8601()
     *      - rfc822          (1|0)   Use Date::rfc822()
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string   The code
     */
    public static function activityReportDate(ArrayObject $attr): string
    {
        $format = '';
        if (!empty($attr['format'])) {
            $format = addslashes($attr['format']);
        }

        $iso8601 = !empty($attr['iso8601']);
        $rfc822  = !empty($attr['rfc822']);

        $f = dcCore::app()->tpl->getFilters($attr);

        if ($rfc822) {
            return '<?php echo ' . sprintf($f, Date::class . '::rfc822(strtotime(dcCore::app()->ctx->activityreports->activity_dt),dcCore::app()->blog->settings->system->blog_timezone)') . '; ?>';
        } elseif ($iso8601) {
            return '<?php echo ' . sprintf($f, Date::class . '::iso8601(strtotime(dcCore::app()->ctx->activityreports->activity_dt),dcCore::app()->blog->settings->system->blog_timezone)') . '; ?>';
        } elseif (!empty($format)) {
            return '<?php echo ' . sprintf($f, Date::class . "::dt2str('" . $format . "',dcCore::app()->ctx->activityreports->activity_dt)") . '; ?>';
        }

        return '<?php echo ' . sprintf($f, Date::class . '::dt2str(dcCore::app()->blog->settings->system->date_format,dcCore::app()->ctx->activityreports->activity_dt)') . '; ?>';
    }
}
