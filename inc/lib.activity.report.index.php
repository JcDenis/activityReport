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

class activityReportLib
{
    public static function logTab($core,$title,$global=false)
    {
        $O =& $core->activityReport;
        if ($global)
        {
            $O->setGlobal();
            $t = 'super';
        }
        else
        {
            $t = 'blog';
        }

        $params = array();
        $logs = $O->getLogs($params);

        ?>
        <div class="multi-part" id="<?php echo $t; ?>_logs" title="<?php echo $title; ?>">
        <?php

        if ($logs->isEmpty())
        {
            echo '<p>'.__('No log').'</p>';
        }
        else
        {

            ?>
            <table>
            <thead>
            <tr>
            <th><?php echo __('Action'); ?></th>
            <th><?php echo __('Message'); ?></th>
            <th><?php echo __('Date'); ?></th>
            <?php if ($global) { ?>
            <th><?php echo __('Blog'); ?></th>
            <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php

            while($logs->fetch())
            {
                $off = $global && $logs->activity_blog_status == 1 ?
                    ' offline' : '';
                $date = dt::str(
                    $core->blog->settings->system->date_format.', '.$core->blog->settings->system->time_format,
                    strtotime($logs->activity_dt),
                    $core->auth->getInfo('user_tz')
                );
                $action = $O->getGroups($logs->activity_group,$logs->activity_action);

                if (empty($action)) continue;

                $msg = vsprintf(__($action['msg']),$O->decode($logs->activity_logs));
                ?>
                <tr class="line<?php echo $off; ?>">
                <td class="nowrap"><?php echo __($action['title']); ?></td>
                <td class="maximal"><?php echo $msg; ?></td>
                <td class="nowrap"><?php echo $date; ?></td>
                <?php if ($global) { ?>
                <td class="nowrap"><?php echo $logs->blog_id; ?></td>
                <?php } ?>
                </tr>
                <?php
            }

            ?>
            </tbody>
            </table>
            <?php

        }

        ?>
        </div>
        <?php

        $O->unsetGlobal();
    }
}