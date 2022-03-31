.. include:: /Includes.rst.txt

=========================================================
Deprecation: #85592 - Deprecated site title configuration
=========================================================

See :issue:`85592`

Description
===========

Defining the site title in the sys_template record (`sys_template.sitetitle` field) has been deprecated and should not be
used any longer. This field (database and TCA) will be removed in v11.


Impact
======

The field will be removed in version 11. In version 10 the site title in the sys_template will be used as a
fallback when no Site title is set in the site configuration.


Affected Installations
======================

Instances defining the site title in the sys_template record.


Migration
=========

Copy the site title to the new available field in the site module language configuration.

.. index:: Frontend, NotScanned
