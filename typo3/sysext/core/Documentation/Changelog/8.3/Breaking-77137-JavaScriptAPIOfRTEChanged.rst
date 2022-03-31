
.. include:: /Includes.rst.txt

================================================
Breaking: #77137 - JavaScript API of RTE changed
================================================

See :issue:`77137`

Description
===========

Due to the migration of the RTE from ExtJS to Bootstrap and jQuery, some API methods have been changed or removed.


Impact
======

ExtJS-based plugins will throw JavaScript errors.

The following methods have been removed:

* `onContainerResize`
* `getWindowDimensions`
* `setTabPanelHeight`
* `syncHeight`

The following methods have been changed:

* `openContainerWindow`
* `buildButtonConfig`


Affected Installations
======================

All installations using custom RTE plugins are affected.


Migration
=========

The former `Ext.Window` objects are replaced by Bootstrap modals.

See the list below for a migration of the changed methods:

openContainerWindow
   The third parameter `dimensions` which used to be an array has changed to `height`, containing an integer

buildButtonConfig
   The method takes now two additional arguments: `active` and `severity`. The parameter `active` is a boolean
   value and declares the button being either active or not. The parameter `severity` is an integer representing the
   severity of the button. This should always represent the severity of the modal, use one of the severities defined in
   :js:`TYPO3/CMS/Backend/Severity`.

.. index:: JavaScript, RTE, Backend
