..  include:: /Includes.rst.txt

..  _breaking-105809-1733928218:

==========================================================
Breaking: #105809 - AfterMailerInitializationEvent removed
==========================================================

See :issue:`105809`

Description
===========

The event :php:`\TYPO3\CMS\Core\Mail\Event\AfterMailerInitializationEvent`
has been removed. This event became obsolete with the introduction of the
Symfony-based mailer in TYPO3 v10. It was only able to influence the TYPO3 Core
mailer by calling the :php:`@internal` method :php:`injectMailSettings()` *after*
the settings had already been determined. The event has been removed since it
no longer had a meaningful use case.

Impact
======

Event listeners registered for this event will no longer be triggered.

Affected installations
======================

This event has had little purpose since the switch to the Symfony-based mailer
and is probably not used in most instances. The extension scanner will find
usages.

Migration
=========

Check if this event can be substituted by reconfiguring
:php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']`, or by listening for the event
:php-short:`\TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent` instead.

..  index:: PHP-API, FullyScanned, ext:core
