.. include:: /Includes.rst.txt

.. _breaking-98480-1664546652:

========================================================================
Breaking: #98480 - TCA table "sys_template" is no longer workspace aware
========================================================================

See :issue:`98480`

Description
===========

The TCA database table :sql:`sys_template` is no longer workspace aware:
When changing sys_template records in a workspace in the Backend, this has
immediate effect on Live workspace.

Impact
======

Records of the TypoScript table "sys_template" are not available for editors,
this change should not have impact on editors working on content related
workspace records.

Affected installations
======================

Instances with enabled workspaces extension may see an impact: Editing TypoScript
template records immediately affects live. This is a relatively seldom scenario,
an upgrade wizard is in place to set all workspace aware sys_template records to
deleted, preventing them to leak to live.

Migration
=========

Do not change :sql:`sys_template` rows in workspaces anymore. Any changes will
be published to live on edit.

.. index:: Backend, Frontend, TCA, TypoScript, NotScanned, ext:core
