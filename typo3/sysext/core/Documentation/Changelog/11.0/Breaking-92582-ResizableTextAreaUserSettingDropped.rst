.. include:: /Includes.rst.txt

===========================================================
Breaking: #92582 - Resizable text area user setting dropped
===========================================================

See :issue:`92582`

Description
===========

The user setting "Make text areas flexible" has been dropped and is
no longer available for editors.

When editing records in the backend, text areas now always grow in height up to
the maximum height defined by the 'maximum text area height' in user settings.


Impact
======

The backend is a little less restricted for editors.


Affected Installations
======================

All instances are affected.


Migration
=========

The option has been removed, there is no migration path.

The following User TSconfig settings are obsolete and should be removed:

* :typoscript:`setup.default.resizeTextareas_Flexible`
* :typoscript:`setup.override.resizeTextareas_Flexible`
* :typoscript:`setup.fields.resizeTextareas_Flexible.disabled`

.. index:: Backend, TSConfig, NotScanned, ext:backend
