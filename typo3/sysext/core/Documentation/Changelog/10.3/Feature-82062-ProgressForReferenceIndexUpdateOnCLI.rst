.. include:: /Includes.rst.txt

============================================================
Feature: #82062 - Progress for Reference Index update on CLI
============================================================

See :issue:`82062`

Description
===========

The Reference Index updating process now shows the current status
when looping over each database table, to have a more visualized
status.


Impact
======

Calling `./typo3/sysext/core/bin/typo3 referenceindex:update -c` shows the new output when running the reference update (`-c` is for checking only).

.. index:: CLI, ext:lowlevel
