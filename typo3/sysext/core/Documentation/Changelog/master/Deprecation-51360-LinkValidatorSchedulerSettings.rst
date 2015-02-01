====================================================================================
Deprecation: #51360 - Deprecate mod.tx_linkvalidator namespace in scheduler settings
====================================================================================

Description
===========

Using the :code:`mod.tx_linkvalidator` namespace in the linkvalidator scheduler task
settings is deprecated. To make the setting consistent with TSconfig the namespace
is changed to :code:`mod.linkvalidator`.


Impact
======

Using :code:`mod.tx_linkvalidator` in the scheduler task settings will add an entry
to the deprecation log.


Affected installations
======================

Instances which use the linkvalidator scheduler taks and use custom TSconfig in the task settings.


Migration
=========

Replace :code:`mod.tx_linkvalidator` with :code:`mod.linkvalidator` in all affected scheduler tasks.

