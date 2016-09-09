
.. include:: ../../Includes.txt

=============================================================================================
Feature: #65791 - Use PHP configured sendmail path, if [MAIL][transport] = sendmail is active
=============================================================================================

See :issue:`65791`

Description
===========

The install tool setting `[MAIL][transport_sendmail_command]` is now retrieved automatically from
PHP runtime configuration `sendmail_path` during installation (instead of '/usr/sbin/sendmail -bs').

Impact
======

There are no impacts on current installations.

New installations will have `[MAIL][transport_sendmail_command]` automatically set during installation
using `sendmail_path` from PHP runtime configuration. It can still be changed manually.

As this setting is only used if `[MAIL][transport]` is set to `sendmail`, it doesn't have impact on
other transport schemes.
