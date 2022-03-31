.. include:: /Includes.rst.txt

===================================================
Feature: #89090 - Reports for conflicting redirects
===================================================

See :issue:`89090`

Description
===========

A new Symfony command has been introduced that detects redirects that conflict with pages. The command is marked as
schedulable, thus it can be created as a scheduler task.


Impact
======

If EXT:scheduler and EXT:reports are installed, the redirect status may be checked and presented as an additional report.

The command may be executed via CLI by invoking `./typo3/sysext/core/bin/typo3 redirects:checkintegrity`. The command
accepts the option `--site` which takes a site identifier to take the pages of that site into consideration only.

.. index:: Backend, CLI, ext:redirects
