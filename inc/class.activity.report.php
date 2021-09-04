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

class activityReport
{
    public $core;
    public $con;

    private $ns = 'activityReport';
    private $_global = 0;
    private $blog = null;
    private $table = '';
    private $groups = [];
    private $settings = [];
    private $lock_blog = null;
    private $lock_global = null;

    public function __construct($core, $ns = 'activityReport')
    {
        $this->core =& $core;
        $this->con = $core->con;
        $this->table = $core->prefix . 'activity';
        $this->blog = $core->con->escape($core->blog->id);
        $this->ns = $core->con->escape($ns);

        $this->getSettings();

        # Check if some logs are too olds
        $this->obsoleteLogs();
    }

    public function setGlobal()
    {
        $this->_global = 1;
    }

    public function unsetGlobal()
    {
        $this->_global = 0;
    }

    public function getGroups($group = null, $action = null)
    {
        if ($action !== null) {
            return isset($this->groups[$group]['actions'][$action]) ? 
                $this->groups[$group]['actions'][$action] : null;
        } elseif ($group !== null) {
            return isset($this->groups[$group]) ? 
                $this->groups[$group] : null;
        } else {
            return $this->groups;
        }
    }

    public function addGroup($group, $title)
    {
        $this->groups[$group] = [
            'title' => $title,
            'actions' => []
        ];
        return true;
    }

    public function addAction($group, $action, $title, $msg, $behavior, $function)
    {
        if (!isset($this->groups[$group])) return false;

        $this->groups[$group]['actions'][$action] = [
            'title' => $title,
            'msg' => $msg
        ];
        $this->core->addBehavior($behavior, $function);
        return true;
    }

    private function getSettings()
    {
        $settings = [
            'active' => false,
            'obsolete' => 2419200,
            'interval' => 86400,
            'lastreport' => 0,
            'mailinglist' => [],
            'mailformat' => 'plain',
            'dateformat' => '%Y-%m-%d %H:%M:%S',
            'requests' => [],
            'blogs' => []
        ];

        $this->settings[0] = $this->settings[1] = $settings;

        $rs = $this->con->select(
            'SELECT setting_id, setting_value, blog_id ' .
            'FROM ' . $this->table . '_setting ' .
            "WHERE setting_type='" . $this->ns . "' " .
            "AND (blog_id='" . $this->blog . "' OR blog_id IS NULL) " .
            'ORDER BY setting_id DESC '
        );

        while($rs->fetch()) {
            $k = $rs->f('setting_id');
            $v = $rs->f('setting_value');
            $b = $rs->f('blog_id');
            $g = $b === null ? 1 : 0;

            if (isset($settings[$k])) {
                $this->settings[$g][$k] = self::decode($v);
            }
        }
        # Force blog
        $this->settings[0]['blogs'] = [1 => $this->blog];
    }

    public function getSetting($n)
    {
        return $this->settings[$this->_global][$n] ?? null;
    }

    public function setSetting($n, $v)
    {
        if (!isset($this->settings[$this->_global][$n])) {
            return null;
        }

        $c = $this->delSetting($n);

        $cur = $this->con->openCursor($this->table . '_setting');
        $this->con->writeLock($this->table . '_setting');

        $cur->blog_id = $this->_global ? null : $this->blog;
        $cur->setting_id = $this->con->escape($n);
        $cur->setting_type = $this->ns;
        $cur->setting_value = (string) self::encode($v);

        $cur->insert();
        $this->con->unlock();

        $this->settings[$this->_global][$n] = $v;

        return true;
    }

    private function delSetting($n)
    {
        return $this->con->execute(
            'DELETE FROM ' . $this->table . '_setting ' .
            "WHERE blog_id" . ($this->_global ? ' IS NULL' : "='" . $this->blog . "'") . ' ' .
            "AND setting_id='" . $this->con->escape($n) . "' " .
            "AND setting_type='" . $this->ns . "' "
        );
    }

    // Action params to put in params['sql']
    public static function requests2params($requests)
    {
        $r = [];
        foreach($requests as $group => $actions) {
            foreach($actions as $action => $is) {
                $r[] = "activity_group='" . $group ."' AND activity_action='" . $action . "' ";
            }
        }
        return empty($r) ? '' : 'AND (' . implode('OR ', $r) . ') ';
    }

    public function getLogs($p, $count_only = false)
    {
        if ($count_only) {
            $r = 'SELECT count(E.activity_id) ';
        } else {
            $content_r = empty($p['no_content']) ? 'activity_logs, ' : '';

            if (!empty($params['columns']) && is_array($params['columns'])) {
                $content_r .= implode(', ', $params['columns']) . ', ';
            }

            $r =
            'SELECT E.activity_id, E.blog_id, B.blog_url, B.blog_name, ' . $content_r .
            'E.activity_group, E.activity_action, E.activity_dt, ' .
            'E.activity_blog_status, E.activity_super_status ';
        }

        $r .= 
        'FROM ' . $this->table . ' E ' .
        'LEFT JOIN ' . $this->core->prefix . 'blog B on E.blog_id=B.blog_id ';

        if (!empty($p['from'])) {
            $r .= $p['from'] . ' ';
        }

        if ($this->_global) {
            $r .= "WHERE E.activity_super_status = 0 ";
        } else {
            $r .= "WHERE E.activity_blog_status = 0 ";
        }

        if (!empty($p['activity_type'])) {
            $r .= "AND E.activity_type = '" . $this->con->escape($p['activity_type']) . "' ";
        } else {
            $r .= "AND E.activity_type = '" . $this->ns . "' ";
        }

        if (!empty($p['blog_id'])) {
            if(is_array($p['blog_id'])) {
                $r .= 'AND E.blog_id' . $this->con->in($p['blog_id']);
            } else {
                $r .= "AND E.blog_id = '" . $this->con->escape($p['blog_id']) . "' ";
            }
        } elseif($this->_global) {
            $r .= 'AND E.blog_id IS NOT NULL ';
        } else {
            $r .= "AND E.blog_id='" . $this->blog . "' ";
        }

        if (isset($p['activity_group'])) {
            if (is_array($p['activity_group']) && !empty($p['activity_group'])) {
                $r .= 'AND E.activity_group ' . $this->con->in($p['activity_group']);
            } elseif ($p['activity_group'] != '') {
                $r .= "AND E.activity_group = '" . $this->con->escape($p['activity_group']) . "' ";
            }
        }

        if (isset($p['activity_action'])) {
            if (is_array($p['activity_action']) && !empty($p['activity_action'])) {
                $r .= 'AND E.activity_action ' . $this->con->in($p['activity_action']);
            } elseif ($p['activity_action'] != '') {
                $r .= "AND E.activity_action = '" . $this->con->escape($p['activity_action']) . "' ";
            }
        }

        if (isset($p['activity_blog_status'])) {
            $r .= "AND E.activity_blog_status = " . ((integer) $p['activity_blog_status']) . " ";
        }

        if (isset($p['activity_super_status'])) {
            $r .= "AND E.activity_super_status = " . ((integer) $p['activity_super_status']) . " ";
        }

        if (isset($p['from_date_ts'])) {
            $dt = date('Y-m-d H:i:s',$p['from_date_ts']);
            $r .= "AND E.activity_dt >= TIMESTAMP '" . $dt . "' ";
        }
        if (isset($p['to_date_ts'])) {
            $dt = date('Y-m-d H:i:s',$p['to_date_ts']);
            $r .= "AND E.activity_dt < TIMESTAMP '" . $dt . "' ";
        }

        if (!empty($p['sql'])) {
            $r .= $p['sql'] . ' ';
        }

        if (!$count_only) {
            if (!empty($p['order'])) {
                $r .= 'ORDER BY ' . $this->con->escape($p['order']) . ' ';
            } else {
                $r .= 'ORDER BY E.activity_dt DESC ';
            }
        }

        if (!$count_only && !empty($p['limit'])) {
            $r .= $this->con->limit($p['limit']);
        }

        return $this->con->select($r);
    }

    public function addLog($group, $action, $logs)
    {
        try {
            $cur = $this->con->openCursor($this->table);
            $this->con->writeLock($this->table);

            $cur->activity_id = $this->getNextId();
            $cur->activity_type = $this->ns;
            $cur->blog_id = $this->blog;
            $cur->activity_group = $this->con->escape((string) $group);
            $cur->activity_action = $this->con->escape((string) $action);
            $cur->activity_logs = self::encode($logs);
            $cur->activity_dt = date('Y-m-d H:i:s');

            $cur->insert();
            $this->con->unlock();
        } catch (Exception $e) {
            $this->con->unlock();
            $this->core->error->add($e->getMessage());
        }

        // Test if email report is needed
        $this->needReport();
    }

    private function parseLogs($rs)
    {
        if ($rs->isEmpty()) {
            return '';
        }

        // @todo move this in function
        include dirname(__FILE__) . '/lib.parselogs.config.php';

        $from = time();
        $to = 0;
        $res = $blog = $group = '';
        $tz = $this->_global ? 'UTC' : $this->core->blog->settings->system->blog_timezone;

        $dt = $this->settings[$this->_global]['dateformat'];
        $dt = empty($dt) ? '%Y-%m-%d %H:%M:%S' : $dt;

        $tpl = $this->settings[$this->_global]['mailformat'];
        $tpl = $tpl == 'html' ? $format['html'] : $format['plain'];

        $blog_open = $group_open = false;

        while($rs->fetch()) {
            // blog
            if ($rs->blog_id != $blog && $this->_global) {
                if ($group_open) {
                    $res .= $tpl['group_close'];
                    $group_open = false;
                }
                if ($blog_open) {
                    $res .= $tpl['blog_close'];
                }

                $blog = $rs->blog_id;
                $group = '';

                $res .= str_replace(
                    ['%TEXT%', '%URL%'], 
                    [$rs->blog_name . ' (' . $rs->blog_id . ')', $rs->blog_url], 
                    $tpl['blog_title']
                ) . $tpl['blog_open'];

                $blog_open = true;
            }

            if (isset($this->groups[$rs->activity_group])) {
                // Type
                if ($rs->activity_group != $group) {
                    if ($group_open) {
                        $res .= $tpl['group_close'];
                    }

                    $group = $rs->activity_group;

                    $res .= str_replace(
                        '%TEXT%',
                        __($this->groups[$group]['title']),
                        $tpl['group_title']
                    ) . $tpl['group_open'];

                    $group_open = true;
                }

                // Action
                $time = strtotime($rs->activity_dt);
                $data = self::decode($rs->activity_logs);

                $res .= str_replace(
                    ['%TIME%', '%TEXT%'], 
                    [dt::str($dt, $time, $tz), vsprintf(__($this->groups[$group]['actions'][$rs->activity_action]['msg']), $data)],
                    $tpl['action']
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
            $res .= $tpl['group_close'];
        }
        if ($blog_open) {
            $res .= $tpl['blog_close'];
        }
        if ($to == 0) {
            $res .= str_replace('%TEXT%', __('An error occured when parsing report.'), $tpl['error']);
        }

        // Top of msg
        if (empty($res)) {
            return '';
        }

        $period = str_replace(
            '%TEXT%',
            __('Activity report'),
            $tpl['period_title']
        ) . $tpl['period_open'];

        $period .= str_replace(
            '%TEXT%',
            __("You received a message from your blog's activity report module."),
            $tpl['info']
        );
        if (!$this->_global) {
            $period .= str_replace('%TEXT%', $rs->blog_name,$tpl['info']);
            $period .= str_replace('%TEXT%', $rs->blog_url,$tpl['info']);
        }
        $period .= str_replace(
            '%TEXT%',
            sprintf(__('Period from %s to %s'), dt::str($dt, $from, $tz), dt::str($dt, $to, $tz)),
            $tpl['info']);
        $period .= $tpl['period_close'];

        $res = str_replace(['%PERIOD%', '%TEXT%'], [$period, $res], $tpl['page']);

        return $res;
    }

    private function obsoleteLogs()
    {
        // Get blogs and logs count
        $rs = $this->con->select(
            "SELECT blog_id " .
            'FROM ' . $this->table . ' ' .
            "WHERE activity_type='" . $this->ns . "' " .
            'GROUP BY blog_id '
        );

        if ($rs->isEmpty()) {
            return null;
        }

        while ($rs->fetch()) {
            $ts = time();
            $obs_blog = dt::str('%Y-%m-%d %H:%M:%S', $ts - (integer) $this->settings[0]['obsolete']);
            $obs_global = dt::str('%Y-%m-%d %H:%M:%S', $ts - (integer) $this->settings[1]['obsolete']);

            $this->con->execute(
                'DELETE FROM ' . $this->table . ' ' .
                "WHERE activity_type='" . $this->ns . "' " .
                "AND (activity_dt < TIMESTAMP '" . $obs_blog . "' " .
                "OR activity_dt < TIMESTAMP '" . $obs_global . "') " .
                "AND blog_id = '" . $this->con->escape($rs->blog_id) ."' "
            );

            if ($this->con->changes()) {
                try {
                    $cur = $this->con->openCursor($this->table);
                    $this->con->writeLock($this->table);

                    $cur->activity_id = $this->getNextId();
                    $cur->activity_type = $this->ns;
                    $cur->blog_id = $rs->blog_id;
                    $cur->activity_group = 'activityReport';
                    $cur->activity_action = 'message';
                    $cur->activity_logs = self::encode(__('Activity report deletes some old logs.'));
                    $cur->activity_dt = date('Y-m-d H:i:s');

                    $cur->insert();
                    $this->con->unlock();
                } catch (Exception $e) {
                    $this->con->unlock();
                    $this->core->error->add($e->getMessage());
                }
            }
        }
    }

    private function cleanLogs()
    {
        $this->con->execute(
            'DELETE FROM ' . $this->table . ' ' .
            "WHERE activity_type='" . $this->ns . "' " .
            "AND activity_blog_status = 1 " .
            "AND activity_super_status = 1 "
        );
    }

    public function deleteLogs()
    {
        if (!$this->core->auth->isSuperAdmin()) {
            return null;
        }

        return $this->con->execute(
            'DELETE FROM ' . $this->table . ' ' .
            "WHERE activity_type='" . $this->ns . "' "
        );
    }

    private function updateStatus($from_date_ts, $to_date_ts)
    {
        $r = 'UPDATE ' . $this->table . ' ';

        if ($this->_global) {
            $r .= "SET activity_super_status = 1 WHERE blog_id IS NOT NULL ";
        } else {
            $r .= "SET activity_blog_status = 1 WHERE blog_id = '" . $this->blog . "' ";
        }
        $r .=
            "AND activity_type = '" . $this->ns . "' " .
            "AND activity_dt >= TIMESTAMP '" . date('Y-m-d H:i:s',$from_date_ts) . "' " .
            "AND activity_dt < TIMESTAMP '" . date('Y-m-d H:i:s',$to_date_ts) . "' ";

        $this->con->execute($r);
    }

    public function getNextId()
    {
        return $this->con->select(
            'SELECT MAX(activity_id) FROM ' . $this->table
        )->f(0) + 1;
    }

    # Lock a file to see if an update is ongoing
    public function lockUpdate()
    {
        try {
            # Need flock function
            if (!function_exists('flock')) {
                throw New Exception("Can't call php function named flock");
            }
            # Cache writable ?
            if (!is_writable(DC_TPL_CACHE)) {
                throw new Exception("Can't write in cache fodler");
            }
            # Set file path
            $f_md5 = $this->_global ? md5(DC_MASTER_KEY) : md5($this->blog);
            $cached_file = sprintf('%s/%s/%s/%s/%s.txt',
                DC_TPL_CACHE,
                'activityreport',
                substr($f_md5, 0, 2),
                substr($f_md5, 2, 2),
                $f_md5
            );
            # Real path
            $cached_file = path::real($cached_file, false);
            // make dir
            if (!is_dir(dirname($cached_file))) {
                    files::makeDir(dirname($cached_file), true);
            }
            //ake file
            if (!file_exists($cached_file)) {
                !$fp = @fopen($cached_file, 'w');
                if ($fp === false) {
                    throw New Exception("Can't create file");
                }
                fwrite($fp, '1', strlen('1'));
                fclose($fp);
            }
            // open file
            if (!($fp = @fopen($cached_file, 'r+'))) {
                throw New Exception("Can't open file");
            }
            // lock file
            if (!flock($fp, LOCK_EX)) {
                throw New Exception("Can't lock file");
            }
            if ($this->_global) {
                $this->lock_global = $fp;
            } else {
                $this->lock_blog = $fp;
            }
            return true;
        } catch (Exception $e) {
            // what ?
            throw $e;
        }
        return false;
    }

    public function unlockUpdate()
    {
        if ($this->_global) {
            @fclose($this->lock_global);
            $this->lock_global = null;
        } else {
            @fclose($this->lock_blog);
            $this->lock_blog = null;
        }
    }

    public static function hasMailer()
    {
        return function_exists('mail') || function_exists('_mail');
    }

    public function needReport($force = false)
    {
        try {
            // Check if server has mail function
            if (!self::hasMailer()) {
                throw new Exception('No mail fonction');
            }

            // Limit to one update at a time
            $this->lockUpdate();

            $send = false;
            $now = time();

            $active = (boolean) $this->settings[$this->_global]['active'];
            $mailinglist = $this->settings[$this->_global]['mailinglist'];
            $mailformat = $this->settings[$this->_global]['mailformat'];
            $requests = $this->settings[$this->_global]['requests'];
            $lastreport = (integer) $this->settings[$this->_global]['lastreport'];
            $interval = (integer) $this->settings[$this->_global]['interval'];
            $blogs = $this->settings[$this->_global]['blogs'];

            if ($force) {
                $lastreport = 0;
            }

            // Check if report is needed
            if ($active && !empty($mailinglist) && !empty($requests) && !empty($blogs) 
                && ($lastreport + $interval) < $now
            ) {
                // Get datas
                $params = [
                    'from_date_ts' => $lastreport,
                    'to_date_ts' => $now,
                    'blog_id' => $blogs,
                    'sql' => self::requests2params($requests),
                    'order' => 'blog_id ASC, activity_group ASC, activity_action ASC, activity_dt ASC '
                ];

                $logs = $this->getLogs($params);
                if (!$logs->isEmpty()) {
                    // Datas to readable text
                    $content = $this->parseLogs($logs);
                    if (!empty($content)) {
                        // Send mails
                        $send = $this->sendReport($mailinglist, $content, $mailformat);
                    }
                }

                // Update db
                if ($send || $this->_global) { // if global : delete all blog logs even if not selected
                    $this->updateStatus($lastreport, $now);
                    $this->cleanLogs();
                    $this->setSetting('lastreport', $now);
                }
            }

            // If this is on a blog, we need to test superAdmin report
            if (!$this->_global) {
                $this->_global = true;
                $this->needReport();
                $this->_global = false;

                if ($send) {
                    $this->core->callBehavior('messageActivityReport', 'Activity report has been successfully send by mail.');
                }
            }
            $this->unlockUpdate();
        } catch (Exception $e) {
            $this->unlockUpdate();
            //throw $e;
        }
        return true;
    }

    private function sendReport($recipients, $message, $mailformat =' ')
    {
        if (!is_array($recipients) || empty($message)) {
            return false;
        }
        $mailformat = $mailformat == 'html' ? 'html'  : 'plain';

        // Checks recipients addresses
        $rc2 = [];
        foreach ($recipients as $v) {
            $v = trim($v);
            if (!empty($v) && text::isEmail($v)) {
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
                ($this->_global ? '[' . $this->core->blog->name . '] ' : '') . __('Blog activity report'), 
                'UTF-8', 'B'
            );

            $headers = [];
            $headers[] = 'From: ' . (defined('DC_ADMIN_MAILFROM') && DC_ADMIN_MAILFROM ? DC_ADMIN_MAILFROM : 'dotclear@local');
            $headers[] = 'Content-Type: text/' . $mailformat .'; charset=UTF-8;';
            //$headers[] = 'MIME-Version: 1.0';
            //$headers[] = 'X-Originating-IP: ' . mb_encode_mimeheader(http::realIP(), 'UTF-8', 'B');
            //$headers[] = 'X-Mailer: Dotclear';
            //$headers[] = 'X-Blog-Id: ' . mb_encode_mimeheader($this->core->blog->id), 'UTF-8', 'B');
            //$headers[] = 'X-Blog-Name: ' . mb_encode_mimeheader($this->core->blog->name), 'UTF-8', 'B');
            //$headers[] = 'X-Blog-Url: ' . mb_encode_mimeheader($this->core->blog->url), 'UTF-8', 'B');

            $done = true;
            foreach ($recipients as $email) {
                if (true !== mail::sendMail($email, $subject, $message, $headers)) {
                    $done = false;
                }
            }
        } catch (Exception $e) {var_dump($e);
            $done = false;
        }
        return $done;
    }

    public function getUserCode()
    {
        $code =
        pack('a32', $this->core->auth->userID()) .
        pack('H*', crypt::hmac(DC_MASTER_KEY, $this->core->auth->getInfo('user_pwd')));
        return bin2hex($code);
    }

    public function checkUserCode($code)
    {
        $code = pack('H*', $code);

        $user_id = trim(@pack('a32', substr($code, 0, 32)));
        $pwd = @unpack('H40hex', substr($code, 32, 40));

        if ($user_id === false || $pwd === false) {
            return false;
        }

        $pwd = $pwd['hex'];

        $strReq = 'SELECT user_id, user_pwd ' .
                'FROM ' . $this->core->prefix . 'user ' .
                "WHERE user_id = '" . $this->core->con->escape($user_id) . "' ";

        $rs = $this->core->con->select($strReq);

        if ($rs->isEmpty()) {
            return false;
        }

        if (crypt::hmac(DC_MASTER_KEY,$rs->user_pwd) != $pwd) {
            return false;
        }

        return $rs->user_id;
    }

    public static function encode($a)
    {
        return @base64_encode(@serialize($a));
    }

    public static function decode($a)
    {
        return @unserialize(@base64_decode($a));
    }
}