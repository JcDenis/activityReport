<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
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
use Throwable;

/**
 * @brief       activityReport main class.
 * @ingroup     activityReport
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ActivityReport
{
    /**
     * Third party plugins ActivityReport class name.
     *
     * @var     string  ACTIVITYREPORT_CLASS_NAME
     */
    public const ACTIVITYREPORT_CLASS_NAME = 'ActivityReportAction';

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

    /** @var null|string  $lock   File lock for update */
    private static $lock = null;

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

    public static function init(): void
    {
        foreach (App::plugins()->getDefines() as $module) {
            $class = $module->get('namespace') . '\\' . self::ACTIVITYREPORT_CLASS_NAME;

            try {
                if (is_a($class, Process::class, true)) {
                    // check class prerequiretics
                    if ($class::init()) {
                        $class::process();
                    }
                }
            } catch (Throwable $e) {
            }
        }
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
     * @return null|MetaRecord    The logs record
     */
    public function getLogs(ArrayObject $params = null, bool $count_only = false, ?SelectStatement $ext_sql = null): ?MetaRecord
    {
        if (is_null($params)) {
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
            ->from($sql->as(App::con()->prefix() . My::ACTIVITY_TABLE_NAME, 'E'), false, true)
            ->join(
                (new JoinStatement())
                    ->left()
                    ->from($sql->as(App::con()->prefix() . App::blog()::BLOG_TABLE_NAME, 'B'))
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

        if (!empty($params['activity_type']) && is_string($params['activity_type'])) {
            $sql->where('E.activity_type = ' . $sql->quote($params['activity_type']));
        } else {
            $sql->where('E.activity_type = ' . $sql->quote($this->type));
        }

        if (isset($params['blog_id']) && is_null($params['blog_id'])) {
            $sql->and('E.blog_id IS NOT NULL');
        } elseif (!empty($params['blog_id'])) {
            if (!is_array($params['blog_id'])) {
                $params['blog_id'] = [$params['blog_id']];
            }
            $sql->and('E.blog_id' . $sql->in($params['blog_id']));
        } else {
            $sql->and('E.blog_id = ' . $sql->quote(App::blog()->id()));
        }

        if (isset($params['activity_status']) && is_numeric($params['activity_status'])) {
            $sql->and('E.activity_status = ' . ((int) $params['activity_status']) . ' ');
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

        if (isset($params['from_date_ts']) && is_numeric($params['from_date_ts'])) {
            $sql->and("E.activity_dt >= TIMESTAMP '" . date('Y-m-d H:i:s', (int) $params['from_date_ts']) . "' ");
        }
        if (isset($params['to_date_ts']) && is_numeric($params['to_date_ts'])) {
            $sql->and("E.activity_dt < TIMESTAMP '" . date('Y-m-d H:i:s', (int) $params['to_date_ts']) . "' ");
        }

        if (!empty($params['requests'])) {
            $or = [];
            foreach ($this->settings->requests as $group => $actions) {
                foreach (array_keys($actions) as $action) {
                    $or[] = $sql->andGroup(['activity_group = ' . $sql->quote((string) $group), 'activity_action = ' . $sql->quote((string) $action)]);
                }
            }
            if (!empty($or)) {
                $sql->and($sql->orGroup($or));
            }
        }

        if (!empty($params['sql'])) {
            $sql->sql($params['sql']);
        }

        if (!$count_only) {
            if (!empty($params['order']) && is_string($params['order'])) {
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
     * @param   string              $group      The group
     * @param   string              $action     The action
     * @param   array<int,string>   $logs       The logs values
     */
    public function addLog(string $group, string $action, array $logs): void
    {
        try {
            $cur = App::con()->openCursor(App::con()->prefix() . My::ACTIVITY_TABLE_NAME);
            App::con()->writeLock(App::con()->prefix() . My::ACTIVITY_TABLE_NAME);

            $cur->setField('activity_id', $this->getNextId());
            $cur->setField('activity_type', $this->type);
            $cur->setField('blog_id', App::blog()->id());
            $cur->setField('activity_group', $group);
            $cur->setField('activity_action', $action);
            $cur->setField('activity_logs', json_encode($logs));
            $cur->setField('activity_dt', Date::str('%Y-%m-%d %H:%M:%S', time(), 'UTC'));
            $cur->setField('activity_status', self::STATUS_PENDING);

            $cur->insert();
            App::con()->unlock();

            # --BEHAVIOR-- coreAfterCategoryCreate -- ActivityReport, cursor
            App::behavior()->callBehavior('activityReportAfteAddLog', $this, $cur);
        } catch (Exception $e) {
            App::con()->unlock();
            App::error()->add($e->getMessage());
        }

        // Test if email report is needed
        $this->needReport();
    }

    /**
     * Parse log message.
     *
     * @param   string              $message    The message to transform
     * @param   array<int,string>   $logs       The log to parse
     *
     * @return  string  The parsed message
     */
    public static function parseMessage(string $message, array $logs): string
    {
        if (!count($logs)) {
            return __('-- activity log is empty --');
        }
        if ($logs[0] == 'undefined') {
            return __('-- activity message is undefined --');
        }
        if ((count($logs) + 1) != count(explode('%s', $message))) {
            return __('-- activity data and message missmatch --');
        }

        return vsprintf($message, $logs);
    }

    /**
     * Parse logs using a format.
     *
     * @param   MetaRecord    $rs     The logs record
     *
     * @return  string  The parsed logs
     */
    private function parseLogs(MetaRecord $rs): string
    {
        $from       = time();
        $to         = 0;
        $res        = $blog_name = $blog_url = $group = '';
        $tz         = App::blog()->settings()->get('system')->get('blog_timezone');
        $tz         = is_string($tz) ? $tz : 'UTC';
        $dt         = empty($this->settings->dateformat) ? '%Y-%m-%d %H:%M:%S' : $this->settings->dateformat;
        $format     = $this->formats->get($this->formats->has($this->settings->mailformat) ? $this->settings->mailformat : 'plain');
        $group_open = false;

        while ($rs->fetch()) {
            $row = new ActivityRow($rs);
            if ($this->groups->has($row->group) && $this->groups->get($row->group)->has($row->action)) {
                // Type
                if ($row->group != $group) {
                    if ($group_open) {
                        $res .= $format->group_close;
                    }

                    $group = $row->group;

                    $res .= str_replace(
                        '%TEXT%',
                        __($this->groups->get($group)->title),
                        $format->group_title
                    ) . $format->group_open;

                    $group_open = true;
                }

                // Action
                $time = strtotime($row->dt);

                $res .= str_replace(
                    ['%TIME%', '%TEXT%'],
                    [Date::str($dt, (int) $time, $tz), vsprintf(__($this->groups->get($group)->get($row->action)->message), $row->logs)],
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
            $blog_name = $row->blog_name;
            $blog_url  = $row->blog_url;
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

        $period .= str_replace('%TEXT%', $blog_name, $format->info);
        $period .= str_replace('%TEXT%', $blog_url, $format->info);

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
        $sql->from(App::con()->prefix() . My::ACTIVITY_TABLE_NAME)
            ->column('blog_id')
            ->where('activity_type =' . $sql->quote($this->type))
            ->group('blog_id');

        $rs = $sql->select();

        if (!$rs || $rs->isEmpty()) {
            return;
        }

        while ($rs->fetch()) {
            $ts  = time();
            $obs = Date::str('%Y-%m-%d %H:%M:%S', $ts - (int) $this->settings->obsolete);
            if (is_string($rs->f('blog_id'))) {
                $sql = new DeleteStatement();
                $sql->from(App::con()->prefix() . My::ACTIVITY_TABLE_NAME)
                    ->where('activity_type =' . $sql->quote($this->type))
                    ->and('activity_dt < TIMESTAMP ' . $sql->quote($obs))
                    ->and('blog_id = ' . $sql->quote($rs->f('blog_id')))
                    ->delete();

                if (App::con()->changes()) {
                    try {
                        $cur = App::con()->openCursor(App::con()->prefix() . My::ACTIVITY_TABLE_NAME);
                        App::con()->writeLock(App::con()->prefix() . My::ACTIVITY_TABLE_NAME);

                        $cur->setField('activity_id', $this->getNextId());
                        $cur->setField('activity_type', $this->type);
                        $cur->setField('blog_id', $rs->f('blog_id'));
                        $cur->setField('activity_group', My::id());
                        $cur->setField('activity_action', 'message');
                        $cur->setField('activity_logs', json_encode([__('Activity report deletes some old logs.')]));
                        $cur->setField('activity_dt', Date::str('%Y-%m-%d %H:%M:%S', time(), 'UTC'));
                        $cur->setField('activity_status', self::STATUS_PENDING);

                        $cur->insert();
                        App::con()->unlock();
                    } catch (Exception $e) {
                        App::con()->unlock();
                        App::error()->add($e->getMessage());
                    }
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

        return $sql->from(App::con()->prefix() . My::ACTIVITY_TABLE_NAME)
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
        $sql->from(App::con()->prefix() . My::ACTIVITY_TABLE_NAME)
            ->column('activity_status')
            ->set((string) self::STATUS_REPORTED)
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
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
        $sql->from(App::con()->prefix() . My::ACTIVITY_TABLE_NAME)
            ->column($sql->max('activity_id'));

        return (int) $sql->select()?->f(0) + 1;
    }

    /**
     * Lock a file to see if an update is ongoing.
     *
     * @return  bool    True if file is locked
     */
    public function lockUpdate(): bool
    {
        try {
            # Cache writable ?
            if (!is_writable(App::config()->cacheRoot())) {
                throw new Exception("Can't write in cache fodler");
            }
            # Set file path
            $f_md5 = md5(App::blog()->id());
            $file  = sprintf(
                '%s/%s/%s/%s/%s.txt',
                App::config()->cacheRoot(),
                My::id(),
                substr($f_md5, 0, 2),
                substr($f_md5, 2, 2),
                $f_md5
            );

            $file = Files::lock($file);
            if (is_null($file) || empty($file)) {
                return false;
            }

            self::$lock = $file;

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Unlock file of update process.
     */
    public function unlockUpdate(): void
    {
        if (!is_null(self::$lock)) {
            Files::unlock(self::$lock);
            self::$lock = null;
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
                    'blog_id'         => App::blog()->id(),
                    'activity_status' => self::STATUS_PENDING,
                    'requests'        => true,
                    'order'           => 'activity_group ASC, activity_action ASC, activity_dt ASC ',
                ]);

                $logs = $this->getLogs($params);
                if (!is_null($logs) && !$logs->isEmpty()) {
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

                    App::behavior()->callBehavior('messageActivityReport', 'Activity report has been successfully send by mail.');
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
     * @param   array<int,string>   $recipients     The recipients
     * @param   string              $message        The message
     * @param   string              $mailformat     The mail content format
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
                sprintf(__('Blog "%s" activity report'), App::blog()->name()),
                'UTF-8',
                'B'
            );

            $headers   = [];
            $headers[] = 'From: ' . (defined('DC_ADMIN_MAILFROM') && str_contains(App::config()->adminMailFrom(), '@') ? App::config()->adminMailFrom() : 'dotclear@local');
            $headers[] = 'Content-Type: text/' . $mailformat . '; charset=UTF-8;';
            //$headers[] = 'MIME-Version: 1.0';
            //$headers[] = 'X-Originating-IP: ' . mb_encode_mimeheader(Http::realIP(), 'UTF-8', 'B');
            //$headers[] = 'X-Mailer: Dotclear';
            //$headers[] = 'X-Blog-Id: ' . mb_encode_mimeheader(App::blog()->id()), 'UTF-8', 'B');
            //$headers[] = 'X-Blog-Name: ' . mb_encode_mimeheader(App::blog()->name()), 'UTF-8', 'B');
            //$headers[] = 'X-Blog-Url: ' . mb_encode_mimeheader(App::>blog()->url()), 'UTF-8', 'B');

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
        $id   = is_string(App::auth()->userID()) ? App::auth()->userID() : '';
        $pw   = is_string(App::auth()->getInfo('user_pwd')) ? App::auth()->getInfo('user_pwd') : '';
        $code = pack('a32', $id) . pack('H*', Crypt::hmac(App::config()->masterKey(), $pw));

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
        $sql->from(App::con()->prefix() . App::auth()::USER_TABLE_NAME)
            ->columns(['user_id', 'user_pwd'])
            ->where('user_id =' . $sql->quote($user_id));

        $rs = $sql->select();

        if (!$rs || $rs->isEmpty() || !is_string($rs->f('user_pwd')) || !is_string($rs->f('user_id'))) {
            return false;
        }

        if (Crypt::hmac(App::config()->masterKey(), $rs->f('user_pwd')) != $pwd) {
            return false;
        }

        return $rs->f('user_id');
    }
}
