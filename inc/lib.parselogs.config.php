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

$format = [
    'html' => [
        'blog_title' => '<h2><a href="%URL%">%TEXT%</a></h2>',
        'blog_open' => '',
        'blog_close' => '',
        'group_title' => '<h3>%TEXT%</h3>',
        'group_open' => '<ul>',
        'group_close' => '</ul>',
        'action' => '<li><em>%TIME%</em><br />%TEXT%</li>',
        'error' => '<p>%TEXT%</p>',
        'period_title' => '<h1>%TEXT%</h1>',
        'period_open' => '<ul>',
        'period_close' => '</ul>',
        'info' => '<li>%TEXT%</li>',
        'page' => 
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n".
            '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n".
            "<head><title>".__('Activity report')."</title>".
            '<style type="text/css">'.
            ' body { color: #303030; background: #FCFCFC; font-size: 0.7em;font-family: Georgia, Tahoma, Arial, Helvetica, sans-serif; }'.
            ' a { color: #303030; text-decoration: none; }'.
            ' h1 { text-align: center; font-size: 2em; }'.
            ' h2 { color: #303030; text-align:center; }'.
            ' h3 { color: #7F3F3F; }'.
            ' li em { color: #303030; }'.
            ' div.info { color: #3F497F; background-color: #F8F8EB; border: 1px solid #888888; margin: 4px; padding: 4px; }'.
            ' div.content { color: #3F7F47; background-color: #F8F8EB; border: 1px solid #888888; margin: 4px; padding: 4px; }'.
            ' div.foot { text-align:center; font-size: 0.9em; }'.
            '</style>'.
            "</head><body>".
            '<div class="info">%PERIOD%</div><div class="content">%TEXT%</div>'.
            '<div class="foot"><p>Powered by <a href="http://dotclear.jcdenis.com/go/activityReport">activityReport</a></p></div>'.
            "</body></html>"
    ],
    'plain' => [
        'blog_title' => "\n--- %TEXT% ---\n",
        'blog_open' => '',
        'blog_close' => '',
        'group_title' => "\n-- %TEXT% --\n\n",
        'group_open' => '',
        'group_close' => '',
        'action' => "- %TIME% : %TEXT%\n",
        'error' => '%TEXT%',
        'period_title' => "%TEXT%\n",
        'period_open' => '',
        'period_close' => '',
        'info' => "%TEXT%\n",
        'page' => 
            "%PERIOD%\n-----------------------------------------------------------\n%TEXT%"
    ]
];