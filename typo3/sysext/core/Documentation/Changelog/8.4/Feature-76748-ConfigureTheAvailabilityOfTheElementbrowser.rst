.. include:: ../../Includes.txt

==================================================================
Feature: #76748 - Configure the availability of the elementbrowser
==================================================================

See :issue:`76748`

Description
===========

The button to open the elementBrowser can be configured to be enabled/disabled for the user.

The button can be disabled by the following TCA setting:
:php:`[table_name]['columns'][field_name]['config']['appearance']['elementBrowserEnabled'] = false;`

The button can be disabled by the following pageTs setting:
:typoscript:`TCEFORM.table_name.field_name.config.appearance.elementBrowserEnabled = 0`

The button can be disabled by the following userTs setting:
:typoscript:`page.TCEFORM.table_name.field_name.config.appearance.elementBrowserEnabled = 0`


Impact
======

Default behavior is that the button stays visible, to disable/hide this button the TCA/pageTs/userTs needs to be explicit changed.

.. index:: TCA, TSConfig, Backend
