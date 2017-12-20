
.. include:: ../../Includes.txt

===================================================================
Breaking: #63838 - Changed OpcodeCacheUtility being a service class
===================================================================

See :issue:`63838`

Description
===========

The `OpcodeCacheUtility` has been migrated to a service class called `OpcodeCacheService`, all methods are not static anymore.


Impact
======

Calling `OpcodeCacheUtility` will throw a fatal error.


Affected Installations
======================

All third-party extensions using the utility class will be affected.


Migration
=========

Create an instance of `OpcodeCacheService` and call its method by the object operator `->`.

Example:

.. code-block:: php

	GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive($cacheEntryPathAndFilename);


.. index:: PHP-API
