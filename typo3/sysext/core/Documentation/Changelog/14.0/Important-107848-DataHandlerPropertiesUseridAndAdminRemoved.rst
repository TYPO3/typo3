.. include:: /Includes.rst.txt

.. _important-107848-1761561877:

========================================================================
Important: #107848 - DataHandler properties `userid` and `admin` removed
========================================================================

See :issue:`107848`

Description
===========

The internal properties :php:`\TYPO3\CMS\Core\DataHandling\DataHandler::$userid`
and :php:`\TYPO3\CMS\Core\DataHandling\DataHandler::$admin` have been removed.

These properties contained information that is already available through the
:php:`BE_USER` property and were therefore redundant.


Impact
======

Accessing these properties directly will result in a fatal PHP error.


Affected Installations
======================

All installations with extensions that access the following properties:

* :php:`\TYPO3\CMS\Core\DataHandling\DataHandler::$userid`
* :php:`\TYPO3\CMS\Core\DataHandling\DataHandler::$admin`

While these properties were marked as :php:`@internal`, they have been commonly
used by extensions, especially the :php:`$admin` property.


Migration
=========

Replace any usage of these properties with the appropriate methods from the
:php:`BE_USER` property:

**For `$userid`:**

.. code-block:: php

    // Before:
    $userId = $dataHandler->userid;

    // After:
    $userId = $dataHandler->BE_USER->getUserId();

**For `$admin`:**

.. code-block:: php

    // Before:
    if ($dataHandler->admin) {
        // do something
    }

    // After:
    if ($dataHandler->BE_USER->isAdmin()) {
        // do something
    }

.. index:: PHP-API, ext:core
