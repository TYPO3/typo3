.. include:: ../../Includes.txt

==========================================================
Deprecation: #80512 - DocumentTemplate->extJScode property
==========================================================

See :issue:`80512`

Description
===========

The property :php:`DocumentTemplate->extJScode` to load ExtJS-specific code "onExtJSReady"
has been marked as deprecated.


Impact
======

If the property is filled and added to the response output, a deprecation warning will be triggered.


Affected Installations
======================

Any installation with custom extensions using (or mis-using) this property to inject ExtJS-specific
code.


Migration
=========

Use the PageRenderer object directly to inject :php:`addExtOnReadyCode` in a backend response.

.. index:: Backend, PHP-API
