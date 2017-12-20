
.. include:: ../../Includes.txt

======================================================================
Breaking: #67815 - Remove tceforms.js because we don't need it anymore
======================================================================

See :issue:`67815`

Description
===========

Since the value slider is based on bootstrap, the last code from `tceforms.js` is not needed anymore.


Impact
======

All instances which include `sysext/backend/Resources/Public/JavaScript/tceforms.js` will produce a 404 Not Found error.


Affected Installations
======================

All instances which include `sysext/backend/Resources/Public/JavaScript/tceforms.js`.


Migration
=========

Remove all references to the file.


.. index:: JavaScript, Backend
