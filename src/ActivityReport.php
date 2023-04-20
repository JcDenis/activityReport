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
use dcAuth;
use dcBlog;
use dcCore;
use dcRecord;
use Dotclear\Database\Statement\{
    DeleteStatement,
    JoinStatement,
    SelectStatement,
    UpdateStatement
};
use Dotclear\Helper\Crypt;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\{
    Files,
    Path
};
use Dotclear\Helper\Network\Mail\Mail;
use Dotclear\Helper\Text;
use Exception;

/**
 * Activity report main class.
 */
class ActivityReport
{
    /** @var    int     activity marked as pending mail report */
    public const STATUS_PENDING = 0;

    /** @var    int     activity marked as reported by mail */
    public const STATUS_REPORTED = 1;

    /** @var    string  $type   Activity report type (by default activityReport) */
    public readonly string $type;

    /** @var    Settings    $settings   Activity report settings for current blog */
    public readonly Settings $settings;

    /** @var    Groups  $groups     Groups of actions */
    public readonly Groups $groups;

    /** @var    Formats  $formats   Export available formats */
    public readonly Formats $formats;

    /** @var    ActivityReport  $instance   ActivityReport instance */
    private static $instance;

    private $lock = null;

    /**
     * Constructor sets activity main type.
     *
     * @param   string  $type   The activity report type
     */
    public function __construct(string $type = null)
    {
        $this->type     = $type ?? My::id();
        $this->settings = new Settings();
        $this->groups   = new Groups();
        $this->formats  = new Formats();

        # Check if some logs are too olds
        $this->obsoleteLogs();
    }

    /**
     * Get singleton instance.
     *
     * @return  ActivityReport  ActivityReport instance
     */
    public static function instance(): ActivityReport
    {
        if (!is_a(self::$instance, ActivityReport::class)) {
            self::$instance = new ActivityReport();
        }

        return self::$instance;
    }

    /**
     * Get logs record.
     *
     * @param   null|ArrayObject        $params         The query params
     * @param   bool                    $count_only     Count only
     * @param   null|SelectStatement    $ext_sql        The sql select statement
     *
     * @return null|dcRecord    The logs record
     */
    public function getLogs(ArrayObject $params = null, bool $count_only = false, ?SelectStatement $ext_sql = null): ?dcRecord
    {
        if (null === $params) {
            $params = new ArrayObject();
        }

        $sql = $ext_sql ? clone $ext_sql : new SelectStatement();

        if ($count_only) {
            $sql->column($sql->count($sql->unique('E.activity_id')));
        } else {
            if (empty($params['no_content'])) {
                $sql->columns([
                    'activity_logs',
                ]);
            }
            if (!empty($params['columns']) && is_array($params['columns'])) {
                $sql->columns($params['columns']);
            }
            $sql->columns([
                'E.activity_id',
                'E.blog_id',
                'B.blog_url',
                'B.blog_name',
                'E.activity_group',
                'E.activity_action',
                'E.activity_dt',
                'E.activity_status',
            ]);
        }
        $sql
            ->from($sql->as(dcCore::app()->prefix . My::ACTIVITY_TABLE_NAME, 'E'), false, true)
            ->join(
                (new JoinStatement())
                    ->left()
                    ->from($sql->as(dcCore::app()->prefix . dcBlog::BLOG_TABLE_NAME, 'B'))
                    ->on('E.blog_id = B.blog_id')
                    ->statement()
            );

        if (!empty($params['join'])) {
            $sql->join($params['join']);
        }

        if (!empty($params['from'])) {
            $sql->from($params['from']);
        }

        if (!empty($params['where'])) {
            //nope
        }

        if (!empty($params['activity_type'])) {
            $sql->where('E.activity_type = ' . $sql->quote($params['activity_type']));
        } else {
            $sql->where('E.activity_type = ' . $sql->quote($this->type));
        }

        if (!empty($params['blog_id'])) {
            if (!is_array($params['blog_id'])) {
                $params['blog_id'] = [$params['blog_id']];
            }
            $sql->and('E.blog_id' . $sql->in($params['blog_id']));
        } elseif (is_null($params['blog_id'])) {
            $sql->and('E.blog_id IS NOT NULL');
        } else {
            $sql->and('E.blog_id = ' . $sql->quote((string) dcCore::app()->blog?->id));
        }

        if (isset($params['activity_status'])) {
            $sql->and('E.activity_status = ' . ((int) $params['activity_tatus']) . ' ');
        }
        //$sql->and('E.activity_status = ' . self::STATUS_PENDING);

        if (isset($params['activity_group'])) {
            if (!is_array($params['activity_group'])) {
                $params['activity_group'] = [$params['activity_group']];
            }
            $sql->and('E.activity_group' . $sql->in($params['activity_group']));
        }

        if (isset($params['activity_action'])) {
            if (!is_array($params['activity_action'])) {
                $params['activity_action'] = [$params['activity_action']];
            }
            $sql->and('E.activity_action' . $sql->in($params['activity_action']));
        }

        if (isset($params['from_date_ts'])) {
            $sql->and("E.activity_dt >= TIMESTAMP '" . date('Y-m-d H:i:s', $params['from_date_ts']) . "' ");
        }
        if (isset($params['to_date_ts'])) {
            $sql->and("E.activity_dt < TIMESTAMP '" . date('Y-m-d H:i:s', $params['to_date_ts']) . "' ");
        }

        if (!empty($params['requests'])) {
            $or = [];
            foreach ($this->settings->requests as $group => $actions) {
                if (empty($actions)) {
                    continue;
                }
                foreach ($actions as $action => $is) {
                    $or[] = $sql->andGroup(['activity_group = ' . $sql->quote($group), 'activity_action = ' . $sql->quote($action)]);
                }
            }
            if (empty($or)) {
                $sql->and($sql->orGroup($or));
            }
        }

        if (!empty($params['sql'])) {
            $sql->sql($params['sql']);
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $sql->order($sql->escape($params['order']));
            } else {
                $sql->order('E.activity_dt DESC');
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $sql->limit($params['limit']);
        }
        $rs = $sql->select();

        return $sql->select();
    }

    /**
     * Add a log.
     *
     * @param   string  $group      The group
     * @param   string  $action     The action
     * @param   array   $logs       The logs values
     */
    public function addLog(string $group, string $action, array $logs): void
    {
        try {
            $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . My::ACTIVITY_TABLE_NAME);
            dcCore::app()->con->writeLock(dcCore::app()->prefix . My::ACTIVITY_TABLE_NAME);

            $cur->setField('activity_id', $this->getNextId());
            $cur->setField('activity_type', $this->type);
            $cur->setField('blog_id', (string) dcCore::app()->blog?->id);
            $cur->setField('activity_group', $group);
            $cur->setField('activity_action', $action);
            $cur->setField('activity_logs', json_encode($logs));
            $cur->setField('activity_dt', date('Y-m-d H:i:s'));
            $cur->setField('activity_status', self::STATUS_PENDING);

            $cur->insert();
            dcCore::app()->con->unlock();

            # --BEHAVIOR-- coreAfterCategoryCreate -- ActivityReport, cursor
            dcCore::app()->callBehavior('activityReportAfteAddLog', $this, $cur);
        } catch (Exception $e) {
            dcCore::app()->con->unlock();
            dcCore::app()->error->add($e->getMessage());
        }

        // Test if email report is needed
        $this->needReport();
    }

    /**
     * Parse logs using a format.
     *
     * @param   dcRecord    $rs     The logs record
     *
     * @return  string  The parsed logs
     */
    private function parseLogs(dcRecord $rs): string
    {
        $from       = time();
        $to         = 0;
        $res        = $blog = $group = '';
        $tz         = dcCore::app()->blog?->settings->get('system')->get('blog_timezone');
        $dt         = empty($this->settings->dateformat) ? '%Y-%m-%d %H:%M:%S' : $this->settings->dateformat;
        $format     = $this->formats->get($this->formats->has($this->settings->mailformat) ? $this->settings->mailformat : 'plain');
        $group_open = false;

        while ($rs->fetch()) {
            if ($this->groups->has($rs->f('activity_group')) && $this->groups->get($rs->f('activity_group'))->has($rs->f('activity_action'))) {
                // Type
                if ($rs->f('activity_group') != $group) {
                    if ($group_open) {
                        $res .= $format->group_close;
                    }

                    $group = $rs->f('activity_group');

                    $res .= str_replace(
                        '%TEXT%',
                        __($this->groups->get($group)->title),
                        $format->group_title
                    ) . $format->group_open;

                    $group_open = true;
                }

                // Action
                $time = strtotime($rs->f('activity_dt'));
                $data = json_decode($rs->f('activity_logs'), true);

                $res .= str_replace(
                    ['%TIME%', '%TEXT%'],
                    [Date::str($dt, (int) $time, $tz), vsprintf(__($this->groups->get($group)->get($rs->f('activity_action'))->message), $data)],
                    $format->action
                );

                # Period
                if ($time < $from) {
                    $from = $time;
                }
                if ($time > $to) {
                    $to = $time;
                }
            }
        }

        if ($group_open) {
            $res .= $format->group_close;
        }
        if ($to == 0) {
            $res .= str_replace('%TEXT%', __('An error occured when parsing report.'), $format->error);
        }

        // Top of msg
        if (empty($res)) {
            return '';
        }

        $period = str_replace(
            '%TEXT%',
            __('Activity report'),
            $format->period_title
        ) . $format->period_open;

        $period .= str_replace(
            '%TEXT%',
            __("You received a message from your blog's activity report module."),
            $format->info
        );

        $period .= str_replace('%TEXT%', $rs->f('blog_name'), $format->info);
        $period .= str_replace('%TEXT%', $rs->f('blog_url'), $format->info);

        $period .= str_replace(
            '%TEXT%',
            sprintf(__('Period from %s to %s'), Date::str($dt, (int) $from, $tz), Date::str($dt, (int) $to, $tz)),
            $format->info
        );
        $period .= $format->period_close;

        $res = str_replace(['%PERIOD%', '%TEXT%'], [$period, $res], $format->page);

        return $res;
    }

    /**
     * Delete obsolete logs.
     */
    private function obsoleteLogs(): void
    {
        // Get blogs and logs count
        $sql = new SelectStatement();
        $sql->from(dcCore::app()->prefix . My::ACTIVITY_TABLE_NAME)
            ->where('activity_type =' . $sql->quote($this->type))
            ->group('blog_id');

        $rs = $sql->select();

        if (!$rs || $rs->isEmpty()) {
            return;
        }

        while ($rs->fetch()) {
            $ts  = time();
            $obs = Date::str('%Y-%m-%d %H:%M:%S', $ts - (int) $this->settings->obsolete);

            $sql = new DeleteStatement();
            $sql->from(dcCore::app()->prefix . My::ACTIVITY_TABLE_NAME)
                ->where('activity_type =' . $sql->quote($this->type))
                ->and('activity_dt < TIMESTAMP ' . $sql->quote($obs))
                ->and('blog_id = ' . $sql->quote($rs->f('blog_id')))
                ->delete();

            if (dcCore::app()->con->changes()) {
                try {
                    $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . My::ACTIVITY_TABLE_NAME);
                    dcCore::app()->con->writeLock(dcCore::app()->prefix . My::ACTIVITY_TABLE_NAME);

                    $cur->setField('activity_id', $this->getNextId());
                    $cur->setField('activity_type', $this->type);
                    $cur->setField('blog_id', $rs->f('blog_id'));
                    $cur->setField('activity_group', My::id());
                    $cur->setField('activity_action', 'message');
                    $cur->setField('activity_logs', json_encode([__('Activity report deletes some old logs.')]));
                    $cur->setField('activity_dt', date('Y-m-d H:i:s'));
                    $cur->setField('activity_status', self::STATUS_PENDING);

                    $cur->insert();
                    dcCore::app()->con->unlock();
                } catch (Exception $e) {
                    dcCore::app()->con->unlock();
                    dcCore::app()->error->add($e->getMessage());
                }
            }
        }
    }

    /**
     * Delete logs.
     *
     * @param   bool    $only_reported  Delete only allready reported logs
     *
     * @return  bool    Action done
     */
    public function deleteLogs(bool $only_reported = false): bool
    {
        $sql = new DeleteStatement();

        if ($only_reported) {
            $sql->and('activity_status = ' . self::STATUS_REPORTED);
        }

        return $sql->from(dcCore::app()->prefix . My::ACTIVITY_TABLE_NAME)
            ->where('activity_type = ' . $sql->quote($this->type))
            ->delete();
    }

    /**
     * Update logs status according to time interval.
     *
     * @param   int     $from_date_ts   The start time
     * @param   int     $to_date_ts     The end time
     */
    private function updateStatus(int $from_date_ts, int $to_date_ts): void
    {
        $sql = new UpdateStatement();
        $sql->from(dcCore::app()->prefix . My::ACTIVITY_TABLE_NAME)
            ->column('activity_status')
            ->set((string) self::STATUS_REPORTED)
            ->where('blog_id = ' . $sql->quote((string) dcCore::app()->blog?->id))
            ->and('activity_type =' . $sql->quote($this->type))
            ->and('activity_dt >= TIMESTAMP ' . $sql->quote(date('Y-m-d H:i:s', $from_date_ts)))
            ->and('activity_dt < TIMESTAMP ' . $sql->quote(date('Y-m-d H:i:s', $to_date_ts)))
            ->update();
    }

    /**
     * Get next activity ID.
     *
     * @return  int     The next id
     */
    public function getNextId(): int
    {
        $sql = new SelectStatement();
        $sql->from(dcCore::app()->prefix . My::ACTIVITY_TABLE_NAME)
            ->column($sql->max('activity_id'));

        return (int) $sql->select()?->f(0) + 1;
    }

    /**
     * Lock update.
     *
     * Lock a file to see if an update is ongoing.
     *
     * @return  bool    The lock success
     */
    public function lockUpdate(): bool
    {
        try {
            # Need flock function
            if (!function_exists('flock')) {
                throw new Exception("Can't call php function named flock");
            }
            # Cache writable ?
            if (!is_writable(DC_TPL_CACHE)) {
                throw new Exception("Can't write in cache fodler");
            }
            # Set file path
            $f_md5       = md5((string) dcCore::app()->blog?->id);
            $cached_file = sprintf(
                '%s/%s/%s/%s/%s.txt',
                DC_TPL_CACHE,
                My::CACHE_DIR_NAME,
                substr($f_md5, 0, 2),
                substr($f_md5, 2, 2),
                $f_md5
            );
            # Real path
            $cached_file = (string) Path::real($cached_file, false);
            // make dir
            if (!is_dir(dirname($cached_file))) {
                Files::makeDir(dirname($cached_file), true);
            }
            //ake file
            if (!file_exists($cached_file)) {
                !$fp = @fopen($cached_file, 'w');
                if ($fp === false) {
                    throw new Exception("Can't create file");
                }
                fwrite($fp, '1', strlen('1'));
                fclose($fp);
            }
            // open file
            if (!($fp = @fopen($cached_file, 'r+'))) {
                throw new Exception("Can't open file");
            }
            // lock file
            if (!flock($fp, LOCK_EX | LOCK_NB)) {
                //throw new Exception("Can't lock file");
                return false;
            }

            $this->lock = $fp;

            return true;
        } catch (Exception $e) {
            // what ?
            throw $e;
        }
    }

    /**
     * Unlock update.
     */
    public function unlockUpdate(): void
    {
        if ($this->lock) {
            @fclose($this->lock);
            $this->lock = null;
        }
    }

    /**
     * Check if doctclear has maisl fonction.
     *
     * @return  bool    has mailer
     */
    public static function hasMailer(): bool
    {
        return function_exists('mail') || function_exists('_mail');
    }

    /**
     * Check if blog need report to be sent and send it.
     *
     * @param   bool    $force  Force to send report
     *
     * @return  bool    The success
     */
    public function needReport(bool $force = false): bool
    {
        try {
            // Check if server has mail function
            if (!self::hasMailer()) {
                throw new Exception('No mail fonction');
            }

            // Limit to one update at a time
            $this->lockUpdate();

            $send = false;
            $now  = time();

            $mailinglist = $this->settings->mailinglist;
            $mailformat  = $this->settings->mailformat;
            $requests    = $this->settings->requests;
            $lastreport  = $this->settings->lastreport;
            $interval    = $this->settings->interval;

            if ($force) {
                $lastreport = 0;
            }

            // Check if report is needed
            if (!empty($mailinglist)
                && !empty($requests)
                && ($lastreport + $interval) < $now
            ) {
                // Get datas
                $params = new ArrayObject([
                    'from_date_ts'    => $lastreport,
                    'to_date_ts'      => $now,
                    'blog_id'         => dcCore::app()->blog?->id,
                    'activity_status' => self::STATUS_PENDING,
                    'requests'        => true,
                    'order'           => 'activity_group ASC, activity_action ASC, activity_dt ASC ',
                ]);

                $logs = $this->getLogs($params);
                if ($logs !== null && !$logs->isEmpty()) {
                    // Datas to readable text
                    $content = $this->parseLogs($logs);
                    if (!empty($content)) {
                        // Send mails
                        $send = $this->sendReport($mailinglist, $content, $mailformat);
                    }
                }

                // Update db
                if ($send) {
                    $this->updateStatus($lastreport, $now);
                    $this->settings->set('lastreport', $now);

                    dcCore::app()->callBehavior('messageActivityReport', 'Activity report has been successfully send by mail.');
                }
            }

            $this->unlockUpdate();
        } catch (Exception $e) {
            $this->unlockUpdate();
        }

        return true;
    }

    /**
     * Send a report.
     *
     * @param   array   $recipients     The recipients
     * @param   string  $message        The message
     * @param   string  $mailformat     The mail content format
     *
     * @return  bool    The sent success
     */
    private function sendReport(array $recipients, string $message, string $mailformat = 'plain'): bool
    {
        if (!is_array($recipients) || empty($message)) {
            return false;
        }
        $mailformat = $mailformat == 'html' ? 'html' : 'plain';

        // Checks recipients addresses
        $rc2 = [];
        foreach ($recipients as $v) {
            $v = trim($v);
            if (!empty($v) && Text::isEmail($v)) {
                $rc2[] = $v;
            }
        }
        $recipients = $rc2;
        unset($rc2);

        if (empty($recipients)) {
            return false;
        }

        # Sending mails
        try {
            $subject = mb_encode_mimeheader(
                sprintf(__('Blog "%s" activity report'), dcCore::app()->blog?->name),
                'UTF-8',
                'B'
            );

            $headers   = [];
            $headers[] = 'From: ' . (defined('DC_ADMIN_MAILFROM') && str_contains(DC_ADMIN_MAILFROM, '@') ? DC_ADMIN_MAILFROM : 'dotclear@local');
            $headers[] = 'Content-Type: text/' . $mailformat . '; charset=UTF-8;';
            //$headers[] = 'MIME-Version: 1.0';
            //$headers[] = 'X-Originating-IP: ' . mb_encode_mimeheader(http::realIP(), 'UTF-8', 'B');
            //$headers[] = 'X-Mailer: Dotclear';
            //$headers[] = 'X-Blog-Id: ' . mb_encode_mimeheader(dcCore::app()->blog->id), 'UTF-8', 'B');
            //$headers[] = 'X-Blog-Name: ' . mb_encode_mimeheader(dcCore::app()->blog->name), 'UTF-8', 'B');
            //$headers[] = 'X-Blog-Url: ' . mb_encode_mimeheader(dcCore::app()->blog->url), 'UTF-8', 'B');

            $done = true;
            foreach ($recipients as $email) {
                if (true !== Mail::sendMail($email, $subject, $message, $headers)) {
                    $done = false;
                }
            }
        } catch (Exception $e) {
            $done = false;
        }

        return $done;
    }

    /**
     * Generate current user code for public feed.
     *
     * @return  string The code
     */
    public function getUserCode(): string
    {
        $code = pack('a32', (string) dcCore::app()->auth?->userID()) .
            pack('H*', Crypt::hmac(DC_MASTER_KEY, (string) dcCore::app()->auth?->getInfo('user_pwd')));

        return bin2hex($code);
    }

    /**
     * Check user code from URL.
     *
     * @param   string  $code   The code
     *
     * @return  string|false    The user ID or false
     */
    public function checkUserCode(string $code): string|false
    {
        $code = pack('H*', $code);

        $user_id = trim(@pack('a32', substr($code, 0, 32)));
        $pwd     = @unpack('H40hex', substr($code, 32, 40));

        if (empty($user_id) || $pwd === false) {
            return false;
        }

        $pwd = $pwd['hex'];

        $sql = new SelectStatement();
        $sql->from(dcCore::app()->prefix . dcAuth::USER_TABLE_NAME)
            ->columns(['user_id', 'user_pwd'])
            ->where('user_id =' . $sql->quote($user_id));

        $rs = $sql->select();

        if (!$rs || $rs->isEmpty()) {
            return false;
        }

        if (Crypt::hmac(DC_MASTER_KEY, $rs->f('user_pwd')) != $pwd) {
            return false;
        }

        return $rs->f('user_id');
    }
}
