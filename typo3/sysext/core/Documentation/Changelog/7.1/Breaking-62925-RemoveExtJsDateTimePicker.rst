
.. include:: ../../Includes.txt

======================================================
Breaking: #62925 - ExtJS Ext.ux.DateTimePicker removed
======================================================

See :issue:`62925`

Description
===========

The old ExtJS component Ext.ux.DateTimePicker has been removed and replaced with a
bootstrap alternative. For technical reasons, the feature had to be removed.
Thus the possibility to use "+3d" or "today" in input fields is no longer
available.


Impact
======

Extensions which rely on Ext.ux.DateTimePicker will break.


Migration
=========

Use the new bootstrap DateTimePicker component which can be loaded with
require.js. Example implementations can be found in EXT:belog, EXT:scheduler
and the FormEngine component of the TYPO3 CMS core.
