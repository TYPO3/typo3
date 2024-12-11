..  include:: /Includes.rst.txt

..  _breaking-105809-1733928218:

==========================================================
Breaking: #105809 - AfterMailerInitializationEvent removed
==========================================================

See :issue:`105809`

Description
===========

The event :php:`\TYPO3\CMS\Core\Mail\Event\AfterMailerInitializationEvent`
has been removed. This event became useless with the introduction of the symfony
based mailer in TYPO3 v10 and was only able to influence the core handling by
calling the :php:`@internal` marked method :php:`injectMailSettings()` *after* the
settings have already been determined within the core mailer. The event has been
removed since it did not fit a useful use case anymore.


Impact
======

Event listeners registered for this event will no longer be triggered.


Affected installations
======================

This event did not fit much purpose since the switch to the symfony based
mailer anymore and is probably not used in many instances. The extension
scanner will find usages.


Migration
=========

Check if this event could be substituted by reconfiguring
:php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']`, or by listening on
:php:`\TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent` event instead.

..  index:: PHP-API, FullyScanned, ext:core
