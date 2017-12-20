
.. include:: ../../Includes.txt

====================================================================================
Deprecation: #51360 - Deprecate mod.tx_linkvalidator namespace in scheduler settings
====================================================================================

See :issue:`51360`

Description
===========

Using the `mod.tx_linkvalidator` namespace in the linkvalidator scheduler task
settings has been marked as deprecated. To make the setting consistent with TSconfig the namespace
is changed to `mod.linkvalidator`.


Impact
======

Using `mod.tx_linkvalidator` in the scheduler task settings will throw an deprecation log entry.


Affected installations
======================

Instances which use the linkvalidator scheduler task and use custom TSconfig in the task settings.


Migration
=========

Replace `mod.tx_linkvalidator` with `mod.linkvalidator` in all affected scheduler tasks.


.. index:: TSConfig, ext:linkvalidator, ext:scheduler