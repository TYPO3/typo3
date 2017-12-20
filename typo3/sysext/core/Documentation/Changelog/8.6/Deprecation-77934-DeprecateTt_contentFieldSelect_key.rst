.. include:: ../../Includes.txt

===========================================================
Deprecation: #77934 - Deprecate tt_content field select_key
===========================================================

See :issue:`77934`

Description
===========

The field `select_key` of the table `tt_content` is not used in the core and has been removed.


Impact
======

The field `select_key` is not available by default anymore.


Affected Installations
======================

All installations and extensions using the field `select_key` of the table `tt_content`.


Migration
=========

Install the extension `compatibility7` to enable the field again.

.. index:: TCA, Database