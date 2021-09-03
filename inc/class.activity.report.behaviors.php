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

if (!defined('DC_RC_PATH')) {
    return null;
}

if (!defined('ACTIVITY_REPORT')) {
    return null;
}

// ActivityReport plugin 
$core->activityReport->addGroup('activityReport', __('ActivityReport messages'));

$core->activityReport->addAction(
    'activityReport',
    'message',
    __('Special messages'),
    __('%s'),
    'messageActivityReport',
    ['activityReportBehaviors', 'messageActivityReport']
);

// Blog 
$core->activityReport->addGroup('blog', __('Actions on blog'));

// Not use as it is global : BEHAVIOR adminAfterBlogCreate in admin/blog.php

// from BEHAVIOR adminAfterBlogUpdate in admin/blog_pref.php
$core->activityReport->addAction(
    'blog',
    'update',
    __('updating blog'),
    __('Blog was updated by "%s"'),
    'adminAfterBlogUpdate',
    ['activityReportBehaviors', 'blogUpdate']
);

// from BEHAVIOR publicHeadContent in template
$core->activityReport->addAction(
    'blog',
    'p404',
    __('404 error'),
    __('New 404 error page at "%s"'),
    'publicHeadContent',
    ['activityReportBehaviors', 'blogP404']
);

// Post 
$core->activityReport->addGroup('post', __('Actions on posts'));

// from BEHAVIOR coreAfterPostCreate in inc/core/class.dc.blog.php (DC 2.2)
// duplicate adminAfterPostCreate in admin/post.php
// duplicate adminAfterPostCreate in admin/services.php
$core->activityReport->addAction(
    'post',
    'create',
    __('post creation'),
    __('A new post called "%s" was created by "%s" at %s'),
    'adminAfterPostCreate',
    ['activityReportBehaviors', 'postCreate']
);

// Plugin contribute
// from BEHAVIOR publicAfterPostCreate in plugins/contribute/_public.php
$core->activityReport->addAction(
    'post',
    'create',
    __('post creation'),
    __('A new post called "%s" was created by "%s" at %s'),
    'publicAfterPostCreate',
    ['activityReportBehaviors', 'postCreate']
);

// from BEHAVIOR coreAfterPostUpdate in inc/core/class.dc.blog.php (DC2.2)
// duplicate adminAfterPostUpdate in admin/post.php
$core->activityReport->addAction(
    'post',
    'update',
    __('updating post'),
    __('Post called "%s" has been updated by "%s" at %s'),
    'adminAfterPostUpdate',
    ['activityReportBehaviors', 'postUpdate']
);

// from BEHAVIOR adminBeforePostDelete in admin/posts_actions.php
// from BEHAVIOR adminBeforePostDelete in admin/post.php
$core->activityReport->addAction(
    'post',
    'delete',
    __('post deletion'),
    __('Post called "%s" has been deleted by "%s"'),
    'adminBeforePostDelete',
    ['activityReportBehaviors', 'postDelete']
);

// Wrong attempt on passworded enrty
// from BEHAVIOR urlHandlerServeDocument in inc/public/lib.urlhandlers.php
$core->activityReport->addAction(
    'post',
    'protection',
    __('Post protection'),
    __('An attempt failed on a passworded post with password "%s" at "%s"'),
    'urlHandlerServeDocument',
    ['activityReportBehaviors', 'postPasswordAttempt']
);

// Comment
$core->activityReport->addGroup('comment',__('Actions on comments'));

// from BEHAVIOR coreAfterCommentCreate in inc/core/class.dc.blog.php
// duplicate adminAfterCommentCreate in admin/comment.php
// duplicate publicAfterCommentCreate in inc/public/lib.urlhandlers.php
$core->activityReport->addAction(
    'comment',
    'create',
    __('comment creation'),
    __('A new comment was created by "%s" on post "%s" at %s'),
    'coreAfterCommentCreate',
    ['activityReportBehaviors', 'commentCreate']
);

// from BEHAVIOR coreAfterCommentUpdate in inc/core/class.dc.blog.php
// duplicate adminAfterCommentUpdate in admin/comment.php
$core->activityReport->addAction(
    'comment',
    'update',
    __('updating comment'),
    __('Comment has been updated by "%s" at %s'),
    'coreAfterCommentUpdate',
    ['activityReportBehaviors', 'commentUpdate']
);

// Missing coreBeforeCommentDelete in inc/core/class.dc.blog.php 
// Missing adminBeforeCommentDelete in admin/comment.php

// from BEHAVIOR coreAfterCommentCreate in inc/core/class.dc.blog.php
// duplicate publicAfterTrackbackCreate in inc/core/class.dc.trackback.php
$core->activityReport->addAction(
    'comment',
    'trackback',
    __('trackback creation'),
    __('A new trackback to "%" at "%s" was created on post "%s" at %s'),
    'coreAfterCommentCreate',
    ['activityReportBehaviors', 'trackbackCreate']
);

// Category 
$core->activityReport->addGroup('category', __('Actions on categories'));

// from BEHAVIOR adminAfterCategoryCreate in admin/category.php
$core->activityReport->addAction(
    'category',
    'create',
    __('category creation'),
    __('A new category called "%s" was created by "%s" at %s'),
    'adminAfterCategoryCreate',
    ['activityReportBehaviors', 'categoryCreate']
);

// from BEHAVIOR adminAfterCategoryUpdate in admin/category.php
$core->activityReport->addAction(
    'category',
    'update',
    __('updating category'),
    __('Category called "%s" has been updated by "%s" at %s'),
    'adminAfterCategoryUpdate',
    ['activityReportBehaviors', 'categoryUpdate']
);

// Missing adminBeforeCategoryDelete in admin/category.php

// User 
$core->activityReport->addGroup('user', __('Actions on users'));

// from BEHAVIOR adminAfterUserCreate in admin/user.php
$core->activityReport->addAction(
    'user',
    'create',
    __('user creation'),
    __('A new user named "%s" was created by "%s"'),
    'adminAfterUserCreate',
    ['activityReportBehaviors', 'userCreate']
);

// from BEHAVIOR adminAfterUserUpdated in admin/user.php
$core->activityReport->addAction(
    'user',
    'update',
    __('updating user'),
    __('User named "%s" has been updated by "%s"'),
    'adminAfterUserUpdate',
    ['activityReportBehaviors', 'userUpdate']
);

// from BEHAVIOR adminBeforeUserDelete in admin/users.php
$core->activityReport->addAction(
    'user',
    'delete',
    __('user deletion'),
    __('User named "%s" has been deleted by "%"'),
    'adminBeforeUserDelete',
    ['activityReportBehaviors', 'userDelete']
);

class activityReportBehaviors
{
    public static function messageActivityReport($message)
    {
        global $core;
        $logs = [$message];
        $core->activityReport->addLog('activityReport', 'message', $logs);
    }

    public static function blogUpdate($cur, $blog_id)
    {
        global $core;
        $logs = [$core->auth->getInfo('user_cn')];
        $core->activityReport->addLog('blog', 'update' ,$logs);
    }

    public static function blogP404()
    {
        global $core;
        if ($core->url->type != '404') {
            return null;
        }
        $logs = [$core->blog->url . $_SERVER['QUERY_STRING']];
        $core->activityReport->addLog('blog', 'p404', $logs);
    }

    public static function postCreate($cur, $post_id)
    {
        global $core;
        $type = $cur->post_type ? $cur->post_type : 'post';
        $post_url = $core->blog->getPostURL('', $cur->post_dt, $cur->post_title, $post_id);
        $logs = [
            $cur->post_title,
            $core->auth->getInfo('user_cn'),
            $core->blog->url . $core->url->getBase($type) . '/' . $post_url
        ];
        $core->activityReport->addLog('post', 'create', $logs);
    }

    public static function postUpdate($cur, $post_id)
    {
        global $core;
        $type = $cur->post_type ? $cur->post_type : 'post';
        $post_url = $core->blog->getPostURL('', $cur->post_dt, $cur->post_title, $post_id);
        $logs = [
            $cur->post_title,
            $core->auth->getInfo('user_cn'),
            $core->blog->url . $core->url->getBase($type) . '/' . $post_url
        ];
        $core->activityReport->addLog('post', 'update', $logs);
    }

    public static function postDelete($post_id)
    {
        global $core;
        $posts = $core->blog->getPosts(['post_id' => $post_id, 'limit' => 1]);
        $logs = [
            $posts->post_title,
            $core->auth->getInfo('user_cn')
        ];
        $core->activityReport->addLog('post', 'delete', $logs);
    }

    public static function postPasswordAttempt($result)
    {
        global $core;
        if ($result['tpl'] != 'password-form.html' || empty($_POST['password'])) {
            return null;
        }
        $logs = [
            $_POST['password'],
            http::getSelfURI()
        ];
        $core->activityReport->addLog('post', 'protection', $logs);
    }

    public static function commentCreate($blog, $cur)
    {
        global $core;
        if ($cur->comment_trackback) {
            return null;
        }
        $posts = $core->blog->getPosts(['post_id' => $cur->post_id, 'limit' => 1]);
        $logs = [
            $cur->comment_author,
            $posts->post_title,
            $core->blog->url . $core->url->getBase($posts->post_type) .
                '/' . $posts->post_url . '#c' . $cur->comment_id
        ];
        $core->activityReport->addLog('comment', 'create', $logs);
    }

    public static function commentUpdate($blog, $cur, $old)
    {
        global $core;
        $posts = $core->blog->getPosts(['post_id' => $old->post_id, 'limit' => 1]);

        $logs = [
            $core->auth->getInfo('user_cn'),
            $posts->post_title,
            $core->blog->url . $core->url->getBase($posts->post_type) .
                '/' . $posts->post_url . '#c' . $old->comment_id
        ];
        $core->activityReport->addLog('comment', 'update', $logs);
    }

    public static function trackbackCreate($cur, $comment_id)
    {
        global $core;
        // From blog args are $blog, $cur #thks to bruno
        $c = $cur instanceOf dcBlog ? $comment_id : $cur;
        if (!$c->comment_trackback || !$c->comment_site) {
            return null;
        }
        $posts = $core->blog->getPosts(
            ['post_id' => $c->post_id, 'no_content' => true, 'limit' => 1]);
        if ($posts->isEmpty()) {
            return null;
        }
        $logs = [
            $c->comment_author,
            $c->comment_site,
            $posts->post_title,
            $core->blog->url . $core->url->getBase($posts->post_type) .
                '/' . $posts->post_url
        ];
        $core->activityReport->addLog('comment', 'trackback', $logs);
    }

    public static function categoryCreate($cur, $cat_id)
    {
        global $core;
        $logs = [
            $cur->cat_title,
            $core->auth->getInfo('user_cn'),
            $core->blog->url . $core->url->getBase('category') . '/' . $cur->cat_url
        ];
        $core->activityReport->addLog('category', 'create', $logs);
    }

    public static function categoryUpdate($cur, $cat_id)
    {
        global $core;
        $logs = [
            $cur->cat_title,
            $core->auth->getInfo('user_cn'),
            $core->blog->url . $core->url->getBase('category') . '/' . $cur->cat_url
        ];
        $core->activityReport->addLog('category', 'update', $logs);
    }

    public static function userCreate($cur, $user_id)
    {
        global $core;
        $user_cn = dcUtils::getUserCN(
            $cur->user_id, 
            $cur->user_name,
            $cur->user_firstname, 
            $cur->user_displayname
        );
        $logs = [
            $user_cn,
            $core->auth->getInfo('user_cn')
        ];
        $core->activityReport->addLog('user', 'create', $logs);
    }

    public static function usertUpdate($cur, $user_id)
    {
        global $core;
        $user_cn = dcUtils::getUserCN(
            $cur->user_id, 
            $cur->user_name,
            $cur->user_firstname, 
            $cur->user_displayname
        );
        $logs = [
            $user_cn,
            $core->auth->getInfo('user_cn')
        ];
        $core->activityReport->addLog('user', 'update', $logs);
    }

    public static function userDelete($user_id)
    {
        global $core;
        $users = $core->getUser($id);
        $user_cn = dcUtils::getUserCN(
            $users->user_id, 
            $users->user_name,
            $users->user_firstname, 
            $users->user_displayname
        );
        $logs = [
            $user_cn,
            $core->auth->getInfo('user_cn')
        ];
        $core->activityReport->addLog('user', 'delete', $logs);
    }
}