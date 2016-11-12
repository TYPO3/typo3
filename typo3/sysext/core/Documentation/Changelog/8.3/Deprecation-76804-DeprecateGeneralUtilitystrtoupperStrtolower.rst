
.. include:: ../../Includes.txt

=======================================================================
Deprecation: #76804 - Deprecate GeneralUtility::strtoupper & strtolower
=======================================================================

See :issue:`76804`

Description
===========

The following methods within `GeneralUtility` have been marked as deprecated:

* `strtoupper()`
* `strtolower()`


Impact
======

Calling any of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any installation with a 3rd party extension calling one of the methods in its PHP code.


Migration
=========

Instead of :php:`GeneralUtility::strtoupper($value)` use:

.. code-block:: php

    mb_strtoupper($value, 'utf-8');

Instead of :php:`GeneralUtility::strtolower($value)` use:

.. code-block:: php

    mb_strtolower($value, 'utf-8');

Alternatively use the native implementation of :php:`strtoupper($value)` or :php:`strtolower($value)`
if the handled string consists of ascii characters only.

.. index:: PHP-API
