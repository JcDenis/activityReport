# README

[![Release](https://img.shields.io/github/v/release/jcdenis/activityReport?color=lightblue)](https://github.com/JcDenis/activityReport/releases)
![Date](https://img.shields.io/github/release-date/jcdenis/activityReport?color=red)
[![Dotclear](https://img.shields.io/badge/dotclear-v2.40-137bbb.svg)](https://fr.dotclear.org/download)
[![Repository](https://img.shields.io/badge/repository-official-9ac123.svg)](https://dotclear.org/plugin/detail/activityReport)
[![License](https://img.shields.io/github/license/jcdenis/activityReport?color=white)](https://github.com/JcDenis/activityReport/blob/master/LICENSE)

## ABOUT

_activityReport_ is a plugin for the open-source web publishing software called [Dotclear](https://www.dotclear.org).

> Log and receive your blog activity by email, feed, or on dashboard

A user guide with screenshots is available in French: [guide d'utilisation](https://github.com/JcDenis/activityReport/blob/master/docs/fr/utilisation.md).

## REQUIREMENTS

* Dotclear 2.40
* PHP 8.1+
* System permissions to add table on database
* System permissions to send email (optionnal)
* Dotclear admin permissions

## USAGE

First install _activityReport_, manualy from a zip package or from 
Dotclear plugins repository. (See Dotclear's documentation to know how do this)

Go to ''plugins manager'', expand _activityReport_ information then 
go to ''configure plugin'', fill in form.

Once it's done you can manage your reports from menu 
''Activity report'' on sidebar or you can add dashboard icon.

With the plugin [ClaudetteMcp](https://github.com/JcDenis/ClaudetteMcp), an AI assistant connected to the blog
can read the activity log (`list_activity`, read only), with the permissions of a blog administrator.
Failed attempts on password protected entries are never sent to the assistant. ClaudetteMcp is optional for _activityReport_ (version 0.5 or later).

## LINKS

* [License](https://github.com/JcDenis/activityReport/blob/master/LICENSE)
* [User guide (in French)](https://github.com/JcDenis/activityReport/blob/master/docs/fr/utilisation.md)
* [Packages & details](https://github.com/JcDenis/activityReport/releases) (or on [Repository](https://dotclear.org/plugin/detail/activityReport))
* [Sources & contributions](https://github.com/JcDenis/activityReport))
* [Issues & security](https://github.com/JcDenis/activityReport/issues))
* [Discuss & help](https://forum.dotclear.org/viewtopic.php?id=40504)

## CONTRIBUTORS

* Jean-Christian Denis (author)
* Claude (AI assistant by Anthropic)

You are welcome to contribute to this code.
