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
use dcNsProcess;
use Dotclear\Database\Structure;
use Dotclear\Database\Statement\{
    DropStatement,
    TruncateStatement
};
use Exception;

/**
 * Install process.
 */
class Install extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && dcCore::app()->newVersion(My::id(), dcCore::app()->plugins->moduleInfo(My::id(), 'version'));

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        try {
            self::beforeGrowUp();

            $s = new Structure(dcCore::app()->con, dcCore::app()->prefix);
            $s->__get(My::ACTIVITY_TABLE_NAME)
                ->field('activity_id', 'bigint', 0, false)
                ->field('activity_type', 'varchar', 32, false, "'" . My::id() . "'")
                ->field('blog_id', 'varchar', 32, true)
                ->field('activity_group', 'varchar', 32, false)
                ->field('activity_action', 'varchar', 32, false)
                ->field('activity_logs', 'text', 0, false)
                ->field('activity_dt', 'timestamp', 0, false, 'now()')
                ->field('activity_status', 'smallint', 0, false, 0)

                ->primary('pk_activity', 'activity_id')
                ->index('idx_activity_type', 'btree', 'activity_type')
                ->index('idx_activity_blog_id', 'btree', 'blog_id')
                ->index('idx_activity_action', 'btree', 'activity_group', 'activity_action')
                ->index('idx_activity_status', 'btree', 'activity_status');

            (new Structure(dcCore::app()->con, dcCore::app()->prefix))->synchronize($s);

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return false;
        }
    }

    /**
     * Do some action on previous version before install.
     */
    private static function beforeGrowUp(): void
    {
        $current = dcCore::app()->getVersion('activityReport');

        // sorry not sorry we restart from scratch
        if ($current && version_compare($current, '3.0', '<')) {
            $struct = new Structure(dcCore::app()->con, dcCore::app()->prefix);

            if ($struct->tableExists('activity')) {
                (new TruncateStatement())->from(dcCore::app()->prefix . 'activity')->truncate();
            }
            if ($struct->tableExists('activity_settings')) {
                (new TruncateStatement())->from(dcCore::app()->prefix . 'activity_settings')->truncate();
                (new DropStatement())->from(dcCore::app()->prefix . 'activity_settings')->drop();
            }
        }
    }
}
