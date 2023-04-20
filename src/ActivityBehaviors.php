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

if (!defined('ACTIVITY_REPORT_V2')) {
    return null;
}

class activityReportBehaviors
{
    public static function registerBehaviors()
    {
        // ActivityReport plugin
        dcCore::app()->activityReport->addGroup(basename(dirname(__DIR__)), __('ActivityReport messages'));

        dcCore::app()->activityReport->addAction(
            basename(dirname(__DIR__)),
            'message',
            __('Special messages'),
            __('%s'),
            'messageActivityReport',
            ['activityReportBehaviors', 'messageActivityReport']
        );

        // Blog
        dcCore::app()->activityReport->addGroup('blog', __('Actions on blog'));

        // Not use as it is global : BEHAVIOR adminAfterBlogCreate in admin/blog.php

        // from BEHAVIOR adminAfterBlogUpdate in admin/blog_pref.php
        dcCore::app()->activityReport->addAction(
            'blog',
            'update',
            __('updating blog'),
            __('Blog was updated by "%s"'),
            'adminAfterBlogUpdate',
            ['activityReportBehaviors', 'blogUpdate']
        );

        // from BEHAVIOR publicHeadContent in template
        dcCore::app()->activityReport->addAction(
            'blog',
            'p404',
            __('404 error'),
            __('New 404 error page at "%s"'),
            'publicHeadContent',
            ['activityReportBehaviors', 'blogP404']
        );

        // Post
        dcCore::app()->activityReport->addGroup('post', __('Actions on posts'));

        // from BEHAVIOR coreAfterPostCreate in inc/core/class.dc.blog.php (DC 2.2)
        // duplicate adminAfterPostCreate in admin/post.php
        // duplicate adminAfterPostCreate in admin/services.php
        dcCore::app()->activityReport->addAction(
            'post',
            'create',
            __('post creation'),
            __('A new post called "%s" was created by "%s" at %s'),
            'adminAfterPostCreate',
            ['activityReportBehaviors', 'postCreate']
        );

        // Plugin contribute
        // from BEHAVIOR publicAfterPostCreate in plugins/contribute/_public.php
        dcCore::app()->activityReport->addAction(
            'post',
            'create',
            __('post creation'),
            __('A new post called "%s" was created by "%s" at %s'),
            'publicAfterPostCreate',
            ['activityReportBehaviors', 'postCreate']
        );

        // from BEHAVIOR coreAfterPostUpdate in inc/core/class.dc.blog.php (DC2.2)
        // duplicate adminAfterPostUpdate in admin/post.php
        dcCore::app()->activityReport->addAction(
            'post',
            'update',
            __('updating post'),
            __('Post called "%s" has been updated by "%s" at %s'),
            'adminAfterPostUpdate',
            ['activityReportBehaviors', 'postUpdate']
        );

        // from BEHAVIOR adminBeforePostDelete in admin/post.php
        dcCore::app()->activityReport->addAction(
            'post',
            'delete',
            __('post deletion'),
            __('Post called "%s" has been deleted by "%s"'),
            'adminBeforePostDelete',
            ['activityReportBehaviors', 'postDelete']
        );

        // Wrong attempt on passworded enrty
        // from BEHAVIOR urlHandlerServeDocument in inc/public/lib.urlhandlers.php
        dcCore::app()->activityReport->addAction(
            'post',
            'protection',
            __('Post protection'),
            __('An attempt failed on a passworded post with password "%s" at "%s"'),
            'urlHandlerServeDocument',
            ['activityReportBehaviors', 'postPasswordAttempt']
        );

        // Comment
        dcCore::app()->activityReport->addGroup('comment', __('Actions on comments'));

        // from BEHAVIOR coreAfterCommentCreate in inc/core/class.dc.blog.php
        // duplicate adminAfterCommentCreate in admin/comment.php
        // duplicate publicAfterCommentCreate in inc/public/lib.urlhandlers.php
        dcCore::app()->activityReport->addAction(
            'comment',
            'create',
            __('comment creation'),
            __('A new comment was created by "%s" on post "%s" at %s'),
            'coreAfterCommentCreate',
            ['activityReportBehaviors', 'commentCreate']
        );

        // from BEHAVIOR coreAfterCommentUpdate in inc/core/class.dc.blog.php
        // duplicate adminAfterCommentUpdate in admin/comment.php
        dcCore::app()->activityReport->addAction(
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
        dcCore::app()->activityReport->addAction(
            'comment',
            'trackback',
            __('trackback creation'),
            __('A new trackback to "%" at "%s" was created on post "%s" at %s'),
            'coreAfterCommentCreate',
            ['activityReportBehaviors', 'trackbackCreate']
        );

        // Category
        dcCore::app()->activityReport->addGroup('category', __('Actions on categories'));

        // from BEHAVIOR adminAfterCategoryCreate in admin/category.php
        dcCore::app()->activityReport->addAction(
            'category',
            'create',
            __('category creation'),
            __('A new category called "%s" was created by "%s" at %s'),
            'adminAfterCategoryCreate',
            ['activityReportBehaviors', 'categoryCreate']
        );

        // from BEHAVIOR adminAfterCategoryUpdate in admin/category.php
        dcCore::app()->activityReport->addAction(
            'category',
            'update',
            __('updating category'),
            __('Category called "%s" has been updated by "%s" at %s'),
            'adminAfterCategoryUpdate',
            ['activityReportBehaviors', 'categoryUpdate']
        );

        // Missing adminBeforeCategoryDelete in admin/category.php

        // User
        dcCore::app()->activityReport->addGroup('user', __('Actions on users'));

        // from BEHAVIOR adminAfterUserCreate in admin/user.php
        dcCore::app()->activityReport->addAction(
            'user',
            'create',
            __('user creation'),
            __('A new user named "%s" was created by "%s"'),
            'adminAfterUserCreate',
            ['activityReportBehaviors', 'userCreate']
        );

        // from BEHAVIOR adminAfterUserUpdated in admin/user.php
        dcCore::app()->activityReport->addAction(
            'user',
            'update',
            __('updating user'),
            __('User named "%s" has been updated by "%s"'),
            'adminAfterUserUpdate',
            ['activityReportBehaviors', 'userUpdate']
        );

        // from BEHAVIOR adminBeforeUserDelete in admin/users.php
        dcCore::app()->activityReport->addAction(
            'user',
            'delete',
            __('user deletion'),
            __('User named "%s" has been deleted by "%"'),
            'adminBeforeUserDelete',
            ['activityReportBehaviors', 'userDelete']
        );
    }

    public static function messageActivityReport($message)
    {
        $logs = [$message];
        dcCore::app()->activityReport->addLog(basename(dirname(__DIR__)), 'message', $logs);
    }

    public static function blogUpdate($cur, $blog_id)
    {
        $logs = [dcCore::app()->auth->getInfo('user_cn')];
        dcCore::app()->activityReport->addLog('blog', 'update', $logs);
    }

    public static function blogP404()
    {
        if (dcCore::app()->url->type != '404') {
            return null;
        }
        $logs = [dcCore::app()->blog->url . $_SERVER['QUERY_STRING']];
        dcCore::app()->activityReport->addLog('blog', 'p404', $logs);
    }

    public static function postCreate($cur, $post_id)
    {
        $type     = $cur->post_type ? $cur->post_type : 'post';
        $post_url = dcCore::app()->blog->getPostURL('', $cur->post_dt, $cur->post_title, $post_id);
        $logs     = [
            $cur->post_title,
            dcCore::app()->auth->getInfo('user_cn'),
            dcCore::app()->blog->url . dcCore::app()->url->getBase($type) . '/' . $post_url,
        ];
        dcCore::app()->activityReport->addLog('post', 'create', $logs);
    }

    public static function postUpdate($cur, $post_id)
    {
        $type     = $cur->post_type ? $cur->post_type : 'post';
        $post_url = dcCore::app()->blog->getPostURL('', $cur->post_dt, $cur->post_title, $post_id);
        $logs     = [
            $cur->post_title,
            dcCore::app()->auth->getInfo('user_cn'),
            dcCore::app()->blog->url . dcCore::app()->url->getBase($type) . '/' . $post_url,
        ];
        dcCore::app()->activityReport->addLog('post', 'update', $logs);
    }

    public static function postDelete($post_id)
    {
        $posts = dcCore::app()->blog->getPosts(['post_id' => $post_id, 'limit' => 1]);
        $logs  = [
            $posts->post_title,
            dcCore::app()->auth->getInfo('user_cn'),
        ];
        dcCore::app()->activityReport->addLog('post', 'delete', $logs);
    }

    public static function postPasswordAttempt($result)
    {
        if ($result['tpl'] != 'password-form.html' || empty($_POST['password'])) {
            return null;
        }
        $logs = [
            $_POST['password'],
            http::getSelfURI(),
        ];
        dcCore::app()->activityReport->addLog('post', 'protection', $logs);
    }

    public static function commentCreate($blog, $cur)
    {
        if ($cur->comment_trackback) {
            return null;
        }
        $posts = dcCore::app()->blog->getPosts(['post_id' => $cur->post_id, 'limit' => 1]);
        $logs  = [
            $cur->comment_author,
            $posts->post_title,
            dcCore::app()->blog->url . dcCore::app()->url->getBase($posts->post_type) .
                '/' . $posts->post_url . '#c' . $cur->comment_id,
        ];
        dcCore::app()->activityReport->addLog('comment', 'create', $logs);
    }

    public static function commentUpdate($blog, $cur, $old)
    {
        $posts = dcCore::app()->blog->getPosts(['post_id' => $old->post_id, 'limit' => 1]);

        $logs = [
            dcCore::app()->auth->getInfo('user_cn'),
            $posts->post_title,
            dcCore::app()->blog->url . dcCore::app()->url->getBase($posts->post_type) .
                '/' . $posts->post_url . '#c' . $old->comment_id,
        ];
        dcCore::app()->activityReport->addLog('comment', 'update', $logs);
    }

    public static function trackbackCreate($cur, $comment_id)
    {
        // From blog args are $blog, $cur #thks to bruno
        $c = $cur instanceof dcBlog ? $comment_id : $cur;
        if (!$c->comment_trackback || !$c->comment_site) {
            return null;
        }
        $posts = dcCore::app()->blog->getPosts(
            ['post_id' => $c->post_id, 'no_content' => true, 'limit' => 1]
        );
        if ($posts->isEmpty()) {
            return null;
        }
        $logs = [
            $c->comment_author,
            $c->comment_site,
            $posts->post_title,
            dcCore::app()->blog->url . dcCore::app()->url->getBase($posts->post_type) .
                '/' . $posts->post_url,
        ];
        dcCore::app()->activityReport->addLog('comment', 'trackback', $logs);
    }

    public static function categoryCreate($cur, $cat_id)
    {
        $logs = [
            $cur->cat_title,
            dcCore::app()->auth->getInfo('user_cn'),
            dcCore::app()->blog->url . dcCore::app()->url->getBase('category') . '/' . $cur->cat_url,
        ];
        dcCore::app()->activityReport->addLog('category', 'create', $logs);
    }

    public static function categoryUpdate($cur, $cat_id)
    {
        $logs = [
            $cur->cat_title,
            dcCore::app()->auth->getInfo('user_cn'),
            dcCore::app()->blog->url . dcCore::app()->url->getBase('category') . '/' . $cur->cat_url,
        ];
        dcCore::app()->activityReport->addLog('category', 'update', $logs);
    }

    public static function userCreate($cur, $user_id)
    {
        $user_cn = dcUtils::getUserCN(
            $cur->user_id,
            $cur->user_name,
            $cur->user_firstname,
            $cur->user_displayname
        );
        $logs = [
            $user_cn,
            dcCore::app()->auth->getInfo('user_cn'),
        ];
        dcCore::app()->activityReport->addLog('user', 'create', $logs);
    }

    public static function usertUpdate($cur, $user_id)
    {
        $user_cn = dcUtils::getUserCN(
            $cur->user_id,
            $cur->user_name,
            $cur->user_firstname,
            $cur->user_displayname
        );
        $logs = [
            $user_cn,
            dcCore::app()->auth->getInfo('user_cn'),
        ];
        dcCore::app()->activityReport->addLog('user', 'update', $logs);
    }

    public static function userDelete($user_id)
    {
        $users   = dcCore::app()->getUser($user_id);
        $user_cn = dcUtils::getUserCN(
            $users->user_id,
            $users->user_name,
            $users->user_firstname,
            $users->user_displayname
        );
        $logs = [
            $user_cn,
            dcCore::app()->auth->getInfo('user_cn'),
        ];
        dcCore::app()->activityReport->addLog('user', 'delete', $logs);
    }
}
