===========================================================
Breaking: #62793 - TypoScript config.notification_* removed
===========================================================

Description
===========

The following TypoScript options were removed:

:ts:`config.notification_email_charset`
:ts:`config.notification_email_encoding`
:ts:`config.notification_email_urlmode`


Impact
======

Using those options have no effect anymore.


Affected installations
======================

Instances that set these options in FE TypoScript.


Migration
=========

Those options can be safely removed. They were used with old mail handling and
are substituted with a different engine that rendered those useless.