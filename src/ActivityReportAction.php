<?php

declare(strict_types=1);

namespace Dotclear\Plugin\activityReport;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Database\{
    Cursor,
    MetaRecord
};
use Dotclear\Helper\Network\Http;

/**
 * @brief       activityReport register class.
 * @ingroup     activityReport
 *
 * Register default activities and export mail formats.
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ActivityReportAction extends Process
{
    public static function init(): bool
    {
        return self::status(true);
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $my       = new Group(My::id(), __('ActivityReport messages'));
        $blog     = new Group('blog', __('Actions on blog'));
        $post     = new Group('post', __('Actions on posts'));
        $comment  = new Group('comment', __('Actions on comments'));
        $category = new Group('category', __('Actions on categories'));
        $user     = new Group('user', __('Actions on users'));

        // ActivityReport plugin

        $my->add(new Action(
            'message',
            __('Special messages'),
            __('%s'),
            'messageActivityReport',
            self::messageActivityReport(...)
        ));

        // Not use as it is global : BEHAVIOR adminAfterBlogCreate in admin/blog.php

        // from BEHAVIOR adminAfterBlogUpdate in admin/blog_pref.php
        $blog->add(new Action(
            'update',
            __('updating blog'),
            __('Blog was updated by "%s"'),
            'adminAfterBlogUpdate',
            self::blogUpdate(...)
        ));

        // from BEHAVIOR publicHeadContent in template
        $blog->add(new Action(
            'p404',
            __('404 error'),
            __('New 404 error page at "%s"'),
            'publicHeadContent',
            self::blogP404(...)
        ));

        // from BEHAVIOR coreAfterPostCreate in inc/core/class.dc.blog.php (DC 2.2)
        // duplicate adminAfterPostCreate in admin/post.php
        // duplicate adminAfterPostCreate in admin/services.php
        $post->add(new Action(
            'create',
            __('post creation'),
            __('A new post called "%s" was created by "%s" at %s'),
            'adminAfterPostCreate',
            self::postCreate(...)
        ));

        // Plugin contribute
        // from BEHAVIOR publicAfterPostCreate in plugins/contribute/_public.php
        $post->add(new Action(
            'create',
            __('post creation'),
            __('A new post called "%s" was created by "%s" at %s'),
            'publicAfterPostCreate',
            self::postCreate(...)
        ));

        // from BEHAVIOR coreAfterPostUpdate in inc/core/class.dc.blog.php (DC2.2)
        // duplicate adminAfterPostUpdate in admin/post.php
        $post->add(new Action(
            'update',
            __('updating post'),
            __('Post called "%s" has been updated by "%s" at %s'),
            'adminAfterPostUpdate',
            self::postUpdate(...)
        ));

        // from BEHAVIOR adminBeforePostDelete in admin/post.php
        $post->add(new Action(
            'delete',
            __('post deletion'),
            __('Post called "%s" has been deleted by "%s"'),
            'adminBeforePostDelete',
            self::postDelete(...)
        ));

        // Wrong attempt on passworded enrty
        // from BEHAVIOR urlHandlerServeDocument in inc/public/lib.urlhandlers.php
        $post->add(new Action(
            'protection',
            __('Post protection'),
            __('An attempt failed on a passworded post with password "%s" at "%s"'),
            'urlHandlerServeDocument',
            self::postPasswordAttempt(...)
        ));

        // from BEHAVIOR coreAfterCommentCreate in inc/core/class.dc.blog.php
        // duplicate adminAfterCommentCreate in admin/comment.php
        // duplicate publicAfterCommentCreate in inc/public/lib.urlhandlers.php
        $comment->add(new Action(
            'create',
            __('comment creation'),
            __('A new comment was created by "%s" on post "%s" at %s'),
            'coreAfterCommentCreate',
            self::commentCreate(...)
        ));

        // from BEHAVIOR coreAfterCommentUpdate in inc/core/class.dc.blog.php
        // duplicate adminAfterCommentUpdate in admin/comment.php
        $comment->add(new Action(
            'update',
            __('updating comment'),
            __('Comment has been updated by "%s" at %s'),
            'coreAfterCommentUpdate',
            self::commentUpdate(...)
        ));

        // Missing coreBeforeCommentDelete in inc/core/class.dc.blog.php
        // Missing adminBeforeCommentDelete in admin/comment.php

        // from BEHAVIOR coreAfterCommentCreate in inc/core/class.dc.blog.php
        // duplicate publicAfterTrackbackCreate in inc/core/class.dc.trackback.php
        $comment->add(new Action(
            'trackback',
            __('trackback creation'),
            __('A new trackback to "%" at "%s" was created on post "%s" at %s'),
            'coreAfterCommentCreate',
            self::trackbackCreate(...)
        ));

        // from BEHAVIOR adminAfterCategoryCreate in admin/category.php
        $category->add(new Action(
            'create',
            __('category creation'),
            __('A new category called "%s" was created by "%s" at %s'),
            'adminAfterCategoryCreate',
            self::categoryCreate(...)
        ));

        // from BEHAVIOR adminAfterCategoryUpdate in admin/category.php
        $category->add(new Action(
            'update',
            __('updating category'),
            __('Category called "%s" has been updated by "%s" at %s'),
            'adminAfterCategoryUpdate',
            self::categoryUpdate(...)
        ));

        // Missing adminBeforeCategoryDelete in admin/category.php

        // from BEHAVIOR adminAfterUserCreate in admin/user.php
        $user->add(new Action(
            'create',
            __('user creation'),
            __('A new user named "%s" was created by "%s"'),
            'adminAfterUserCreate',
            self::userCreate(...)
        ));

        // from BEHAVIOR adminAfterUserUpdated in admin/user.php
        $user->add(new Action(
            'update',
            __('updating user'),
            __('User named "%s" has been updated by "%s"'),
            'adminAfterUserUpdate',
            self::userUpdate(...)
        ));

        // from BEHAVIOR adminAfterUserProfileUpdate in admin/preferences.php
        $user->add(new Action(
            'preference',
            __('updating user preference'),
            __('"%s" user preference has been updated'),
            'adminAfterUserProfileUpdate',
            self::userPreference(...)
        ));
        $user->add(new Action(
            'preference',
            __('updating user preference'),
            __('"%s" user preference has been updated'),
            'adminAfterUserOptionsUpdate',
            self::userPreference(...)
        ));
        $user->add(new Action(
            'preference',
            __('updating user preference'),
            __('"%s" user preference has been updated'),
            'adminAfterDashboardOptionsUpdate',
            self::userOption(...)
        ));

        // from BEHAVIOR adminBeforeUserDelete in admin/users.php
        $user->add(new Action(
            'delete',
            __('user deletion'),
            __('User named "%s" has been deleted by "%"'),
            'adminBeforeUserDelete',
            self::userDelete(...)
        ));

        ActivityReport::instance()->groups
            ->add($my)
            ->add($blog)
            ->add($post)
            ->add($comment)
            ->add($category)
            ->add($user);

        // Add default email report formats
        ActivityReport::instance()->formats
            ->add(new Format('plain', [])) // plain text format is build with default values
            ->add(new Format('html', [
                'name'         => __('HTML'),
                'blog_title'   => '<h2><a href="%URL%">%TEXT%</a></h2>',
                'group_title'  => '<h3>%TEXT%</h3>',
                'group_open'   => '<ul>',
                'group_close'  => '</ul>',
                'action'       => '<li><em>%TIME%</em><br />%TEXT%</li>',
                'error'        => '<p>%TEXT%</p>',
                'period_title' => '<h1>%TEXT%</h1>',
                'period_open'  => '<ul>',
                'period_close' => '</ul>',
                'info'         => '<li>%TEXT%</li>',
                'page'         => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n" .
                    '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">' . "\n" .
                    '<head><title>' . __('Activity report') . '</title>' .
                    '<style type="text/css">' .
                    ' body { color: #303030; background: #FCFCFC; font-size: 0.7em;font-family: Georgia, Tahoma, Arial, Helvetica, sans-serif; }' .
                    ' a { color: #303030; text-decoration: none; }' .
                    ' h1 { text-align: center; font-size: 2em; }' .
                    ' h2 { color: #303030; text-align:center; }' .
                    ' h3 { color: #7F3F3F; }' .
                    ' li em { color: #303030; }' .
                    ' div.info { color: #3F497F; background-color: #F8F8EB; border: 1px solid #888888; margin: 4px; padding: 4px; }' .
                    ' div.content { color: #3F7F47; background-color: #F8F8EB; border: 1px solid #888888; margin: 4px; padding: 4px; }' .
                    ' div.foot { text-align:center; font-size: 0.9em; }' .
                    '</style>' .
                    '</head><body>' .
                    '<div class="info">%PERIOD%</div><div class="content">%TEXT%</div>' .
                    '<div class="foot"><p>Powered by <a href="https://github.com/JcDenis/activityReport">activityReport</a></p></div>' .
                    '</body></html>',
            ]));

        return true;
    }

    public static function messageActivityReport(string $message): void
    {
        $logs = [$message];
        ActivityReport::instance()->addLog(My::id(), 'message', $logs);
    }

    public static function blogUpdate(Cursor $cur, string $blog_id): void
    {
        $logs = [self::str(App::auth()->getInfo('user_cn'))];
        ActivityReport::instance()->addLog('blog', 'update', $logs);
    }

    public static function blogP404(): void
    {
        if (App::url()->type != '404') {
            return;
        }
        $logs = [self::str(App::blog()->url()) . $_SERVER['QUERY_STRING']];
        ActivityReport::instance()->addLog('blog', 'p404', $logs);
    }

    public static function postCreate(Cursor $cur, int $post_id): void
    {
        $post_url = App::blog()->getPostURL('', self::str($cur->getField('post_dt')), self::str($cur->getField('post_title')), $post_id);
        $logs     = [
            self::str($cur->getField('post_title')),
            self::str(App::auth()->getInfo('user_cn')),
            self::str(App::blog()->url()) . App::url()->getBase(self::str($cur->getField('post_type'))) . '/' . $post_url,
        ];
        ActivityReport::instance()->addLog('post', 'create', $logs);
    }

    public static function postUpdate(Cursor $cur, int|string $post_id): void
    {
        $post_id  = is_numeric($post_id) ? (int) $post_id : 0;
        $post_url = App::blog()->getPostURL('', self::str($cur->getField('post_dt')), self::str($cur->getField('post_title')), $post_id);
        $logs     = [
            self::str($cur->getField('post_title')),
            self::str(App::auth()->getInfo('user_cn')),
            self::str(App::blog()->url()) . App::url()->getBase(self::str($cur->getField('post_type'))) . '/' . $post_url,
        ];
        ActivityReport::instance()->addLog('post', 'update', $logs);
    }

    public static function postDelete(int $post_id): void
    {
        $posts = App::blog()->getPosts(['post_id' => $post_id, 'limit' => 1]);
        if ($posts->isEmpty()) {
            return;
        }
        $logs = [
            self::str($posts->f('post_title')),
            self::str(App::auth()->getInfo('user_cn')),
        ];
        ActivityReport::instance()->addLog('post', 'delete', $logs);
    }

    /**
     * @param   ArrayObject<string, mixed>  $result
     */
    public static function postPasswordAttempt(ArrayObject $result): void
    {
        if ($result['tpl'] != 'password-form.html' || empty($_POST['password'])) {
            return;
        }
        $logs = [
            $_POST['password'],
            Http::getSelfURI(),
        ];
        ActivityReport::instance()->addLog('post', 'protection', $logs);
    }

    public static function commentCreate(BlogInterface $blog, Cursor $cur): void
    {
        if ($cur->getField('comment_trackback')) {
            return;
        }
        $posts = App::blog()->getPosts(
            ['post_id' => $cur->getField('post_id'), 'limit' => 1, 'post_type' => '']
        );
        if ($posts->isEmpty()) {
            return;
        }

        $logs = [
            self::str($cur->getField('comment_author')),
            self::str($posts->f('post_title')),
            self::str(App::blog()->url()) . App::url()->getBase(self::str($posts->f('post_type'))) .
                '/' . self::str($posts->f('post_url')) . '#c' . self::str($cur->getField('comment_id')),
        ];
        ActivityReport::instance()->addLog('comment', 'create', $logs);
    }

    public static function commentUpdate(BlogInterface $blog, Cursor $cur, MetaRecord $old): void
    {
        $posts = App::blog()->getPosts(
            ['post_id' => $old->f('post_id'), 'limit' => 1]
        );
        if ($posts->isEmpty()) {
            return;
        }

        $logs = [
            self::str(App::auth()->getInfo('user_cn')),
            self::str($posts->f('post_title')),
            self::str(App::blog()->url()) . App::url()->getBase(self::str($posts->f('post_type'))) .
                '/' . self::str($posts->f('post_url')) . '#c' . self::str($old->f('comment_id')),
        ];
        ActivityReport::instance()->addLog('comment', 'update', $logs);
    }

    public static function trackbackCreate(BlogInterface $blog, Cursor $cur): void
    {
        if (!$cur->getField('comment_trackback')) {
            return;
        }

        $posts = App::blog()->getPosts(
            ['post_id' => $cur->getField('post_id'), 'no_content' => true, 'limit' => 1]
        );
        if ($posts->isEmpty()) {
            return;
        }

        $logs = [
            self::str($cur->getField('comment_author')),
            self::str($cur->getField('comment_site')),
            self::str($posts->f('post_title')),
            self::str(App::blog()->url()) . App::url()->getBase(self::str($posts->f('post_type'))) .
                '/' . self::str($posts->f('post_url')),
        ];
        ActivityReport::instance()->addLog('comment', 'trackback', $logs);
    }

    public static function categoryCreate(Cursor $cur, int $cat_id): void
    {
        $logs = [
            self::str($cur->getField('cat_title')),
            self::str(App::auth()->getInfo('user_cn')),
            self::str(App::blog()->url()) . App::url()->getBase('category') . '/' . self::str($cur->getField('cat_url')),
        ];
        ActivityReport::instance()->addLog('category', 'create', $logs);
    }

    public static function categoryUpdate(Cursor $cur, int $cat_id): void
    {
        $logs = [
            self::str($cur->getField('cat_title')),
            self::str(App::auth()->getInfo('user_cn')),
            self::str(App::blog()->url()) . App::url()->getBase('category') . '/' . self::str($cur->getField('cat_url')),
        ];
        ActivityReport::instance()->addLog('category', 'update', $logs);
    }

    public static function userCreate(Cursor $cur, string $user_id): void
    {
        $user_cn = App::users()->getUserCN(
            self::str($cur->getField('user_id')),
            self::str($cur->getField('user_name')),
            self::str($cur->getField('user_firstname')),
            self::str($cur->getField('user_displayname'))
        );
        $logs = [
            self::str($user_cn),
            self::str(App::auth()->getInfo('user_cn')),
        ];
        ActivityReport::instance()->addLog('user', 'create', $logs);
    }

    public static function userUpdate(Cursor $cur, string $user_id): void
    {
        $user_cn = App::users()->getUserCN(
            self::str($cur->getField('user_id')),
            self::str($cur->getField('user_name')),
            self::str($cur->getField('user_firstname')),
            self::str($cur->getField('user_displayname'))
        );
        $logs = [
            self::str($user_cn),
            self::str(App::auth()->getInfo('user_cn')),
        ];
        ActivityReport::instance()->addLog('user', 'update', $logs);
    }

    public static function userPreference(Cursor $cur, string $user_id): void
    {
        self::userOption($user_id);
    }

    public static function userOption(string $user_id): void
    {
        $user = App::users()->getUser($user_id);
        if ($user->isEmpty()) {
            return;
        }
        $user_cn = App::users()->getUserCN(
            self::str($user->f('user_id')),
            self::str($user->f('user_name')),
            self::str($user->f('user_firstname')),
            self::str($user->f('user_displayname'))
        );
        $logs = [
            self::str($user_cn),
        ];
        ActivityReport::instance()->addLog('user', 'preference', $logs);
    }

    public static function userDelete(string $user_id): void
    {
        $users   = App::users()->getUser($user_id);
        $user_cn = App::users()->getUserCN(
            self::str($users->f('user_id')),
            self::str($users->f('user_name')),
            self::str($users->f('user_firstname')),
            self::str($users->f('user_displayname'))
        );
        $logs = [
            self::str($user_cn),
            self::str(App::auth()->getInfo('user_cn')),
        ];
        ActivityReport::instance()->addLog('user', 'delete', $logs);
    }

    /**
     * Type cast.
     *
     * @param   mixed   $field  The field to check
     *
     * @return  string  The string field
     */
    private static function str(mixed $field): string
    {
        return is_string($field) || is_numeric($field) ? (string) $field : 'unknown';
    }
}
