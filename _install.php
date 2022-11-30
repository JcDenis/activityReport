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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

$new_version = dcCore::app()->plugins->moduleInfo('activityReport', 'version');
$old_version = dcCore::app()->getVersion('activityReport');

if (version_compare($old_version, $new_version, '>=')) {
    return null;
}

try {
    $s = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);
    $s->{initActivityReport::ACTIVITY_TABLE_NAME}
        ->activity_id('bigint', 0, false)
        ->activity_type('varchar', 32, false, "'activityReport'")
        ->blog_id('varchar', 32, true)
        ->activity_group('varchar', 32, false)
        ->activity_action('varchar', 32, false)
        ->activity_logs('text', 0, false)
        ->activity_dt('timestamp', 0, false, 'now()')
        ->activity_blog_status('smallint', 0, false, 0)
        ->activity_super_status('smallint', 0, false, 0)

        ->primary('pk_activity', 'activity_id')
        ->index('idx_activity_type', 'btree', 'activity_type')
        ->index('idx_activity_blog_id', 'btree', 'blog_id')
        ->index('idx_activity_action', 'btree', 'activity_group', 'activity_action')
        ->index('idx_activity_blog_status', 'btree', 'activity_blog_status')
        ->index('idx_activity_super_status', 'btree', 'activity_super_status');

    $s->{initActivityReport::SETTING_TABLE_NAME}
        ->setting_id('varchar', 64, false)
        ->blog_id('varchar', 32, true)
        ->setting_type('varchar', 32, false)
        ->setting_value('text', 0, false)

        ->unique('uk_activity_setting', 'setting_id', 'blog_id', 'setting_type')
        ->index('idx_activity_setting_blog_id', 'btree', 'blog_id')
        ->index('idx_activity_setting_type', 'btree', 'setting_type');

    $si      = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);
    $changes = $si->synchronize($s);

    dcCore::app()->setVersion('activityReport', $new_version);

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

return false;
