
.. include:: ../../Includes.txt

========================================================================
Breaking: #77344 - EXT:form - Rename configuration for confirmation view
========================================================================

See :issue:`77344`

Description
===========

The typoscript key :ts:`configuration` for the confirmation configuration has been renamed.

Up until now the layout settings of the confirmation view could be addressed via :ts:`tt_content.mailform.20.confirmation.layout`.
This setting was introduced with patch 28526 but never documented.

Besides this, the confirmation view enable setting can be set via :ts:`tt_content.mailform.20.confirmation = 1`.

To keep the meaning of the settings clear, it was decided to rename the configuration of the confirmation view.


Impact
======

Having the confirmation view enabled and the confirmation configuration customized, a naming collision occurs. As a
result, the confirmation step has been disabled.
Since the configuration was never documented, only few people know about this setting.


Affected Installations
======================

All installations enabling the confirmation view and customizing the layout of this view.


Migration
=========

All occurrences of :ts:`tt_content.mailform.20.confirmation.layout` have to be migrated to :ts:`tt_content.mailform.20.confirmationView.layout`.


.. index:: TypoScript, ext:form
