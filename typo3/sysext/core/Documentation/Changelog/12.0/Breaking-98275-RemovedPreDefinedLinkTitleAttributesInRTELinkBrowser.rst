.. include:: /Includes.rst.txt

.. _breaking-98275-1662540769:

================================================================================
Breaking: #98275 - Removed pre-defined link title attributes in RTE link browser
================================================================================

See :issue:`98275`

Description
===========

Back in the old HTMLArea it was possible to pre-define a link title in the
`classesAnchor` configuration which got applied after selecting the CSS class
for a link. This feature was migrated to EXT:rte_ckeditor in TYPO3 v8.

From an SEO and accessibility point of view, this doesn't make much sense as
this would lead to repetitive usage of the same link title, not helping much at
all.

For those reasons, the possibilities to

* pre-define a link title
* make the link title field read-only

have been removed without substitution.

Impact
======

Pre-configuring a link title based on the applied CSS class is not possible
anymore. Also, configuring the link title field to be read-only is not possible
anymore.

Affected installations
======================

All installations configuring :yaml:`classesAnchor.*.linkText` or
:yaml:`buttons.link.properties.title.readOnly` in an RTE configuration file are
affected.

Migration
=========

There is no migration available. Removing the obsolete settings
:yaml:`classesAnchor.*.linkText` and :yaml:`buttons.link.properties.title.readOnly`
is recommended.

.. index:: Backend, RTE, NotScanned, ext:rte_ckeditor
