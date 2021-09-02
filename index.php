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

if (!defined('DC_CONTEXT_ADMIN')){return;}

if (!defined('ACTIVITY_REPORT')){return;}

dcPage::check('admin');

require_once dirname(__FILE__).'/inc/lib.activity.report.index.php';

$tab = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : 'blog_settings';

?>
<html>
 <head>
  <title><?php echo __('Activity report'); ?></title>
<?php 
echo 
dcPage::jsLoad('js/_posts_list.js').
dcPage::jsToolBar().
dcPage::jsPageTabs($tab).
dcPage::jsLoad('index.php?pf=activityReport/js/main.js').
'<script type="text/javascript">'."\n//<![CDATA[\n".
dcPage::jsVar('jcToolsBox.prototype.text_wait',__('Please wait')).
dcPage::jsVar('jcToolsBox.prototype.section',$section).
"\n//]]>\n</script>\n";
?>
 </head>
<body>
<h2><?php 
 echo html::escapeHTML($core->blog->name).
 ' &rsaquo; '.__('Activity report');
?></h2>

<?php
if (!activityReport::hasMailer())
{
    ?>
    <p class="error"><?php echo __('This server has no mail function, activityReport not send email report.'); ?></p>
    <?php
}
activityReportLib::settingTab($core,__('Settings'));
activityReportLib::logTab($core,__('Logs'));

if ($core->auth->isSuperAdmin())
{
    activityReportLib::settingTab($core,__('Super settings'),true);
    activityReportLib::logTab($core,__('Super logs'),true);
}

?>

<hr class="clear"/>
<p class="right">
activityReport - 
<?php echo $core->plugins->moduleInfo('activityReport','version'); ?>&nbsp;
<img alt="activityReport" src="index.php?pf=activityReport/icon.png" />
</p>
</body>
</html>