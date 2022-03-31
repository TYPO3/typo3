.. include:: /Includes.rst.txt

===============================================================
Deprecation: #92583 - 3 last arguments of wrapClickMenuOnIcon()
===============================================================

See :issue:`92583`

Description
===========

:php:`BackendUtility::wrapClickMenuOnIcon()` has a boolean flag to let the method
return an array with tag parameters instead of a fully build HTML tag as string.
As this are two completely different things and cause problems when analysing
return types it should not be done in the same method.

Calling :php:`BackendUtility::wrapClickMenuOnIcon()` with the 7th and last argument
:php:`$returnTagParameters` set to :php:`true` has been marked as deprecated alongside the 5th
and 6th arguments that are already unused.

A new method has been introduced that returns the aforementioned array.


Impact
======

Calling :php:`BackendUtility::wrapClickMenuOnIcon()` with more than 4 arguments
will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All 3rd party extensions calling :php:`BackendUtility::wrapClickMenuOnIcon()` with more
than 4 arguments are affected.


Migration
=========

Arguments 4 and 5 can be safely removed as they are already unused.

If :php:`$returnTagParameters` was set to :php:`true` the newly introduced method
:php:`BackendUtility::getClickMenuOnIconTagParameters()` should be called to
retrieve the array with the tag parameters.

Example
=======

.. code-block:: php

   $parameters = BackendUtility::getClickMenuOnIconTagParameters($tableName, $uid, 'tree');


.. index:: Backend, FullyScanned, ext:backend
