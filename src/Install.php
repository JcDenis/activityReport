<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Database\Statement\DropStatement;
use Dotclear\Database\Statement\TruncateStatement;
use Exception;

/**
 * @brief       activityReport install class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            self::beforeGrowUp();

            $s = App::db()->structure();
            $s->table(My::ACTIVITY_TABLE_NAME)
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

            App::db()->structure()->synchronize($s);

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }

    /**
     * Do some action on previous version before install.
     */
    private static function beforeGrowUp(): void
    {
        // sorry not sorry we restart from scratch
        if (is_string(App::version()->getVersion('activityReport'))
            && version_compare(App::version()->getVersion('activityReport'), '3.0', '<')
        ) {
            $struct = App::db()->structure();

            if ($struct->tableExists('activity')) {
                (new TruncateStatement())->from(App::db()->con()->prefix() . 'activity')->truncate();
            }
            if ($struct->tableExists('activity_settings')) {
                (new TruncateStatement())->from(App::db()->con()->prefix() . 'activity_settings')->truncate();
                (new DropStatement())->from(App::db()->con()->prefix() . 'activity_settings')->drop();
            }
        }
    }
}
