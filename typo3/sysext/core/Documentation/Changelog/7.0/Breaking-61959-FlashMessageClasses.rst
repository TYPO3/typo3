
.. include:: ../../Includes.txt

======================================================
Breaking: #61959 - Move flash message output to alerts
======================================================

See :issue:`61959`

Description
===========

Flash messages are now styled by using the native CSS classes of Twitter Bootstrap. The changed classes are:

* "typo3-message message-notice" => "alert alert-notice"
* "typo3-message message-information" => "alert alert-info"
* "typo3-message message-ok" => "alert alert-success"
* "typo3-message message-warning" => "alert alert-warning"
* "typo3-message message-error" => "alert alert-danger"

Impact
======

Extensions which use the old classes like "typo3-message message-information" rely on deprecated CSS classes
which might lead to non styled output.

Affected installations
======================

Any installation that uses the HTML of flash messages without calling the API.

Migration
=========

Change the used CSS classes to the new ones.
