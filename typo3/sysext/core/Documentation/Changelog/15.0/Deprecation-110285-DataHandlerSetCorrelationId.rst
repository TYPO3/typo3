.. include:: /Includes.rst.txt

.. _deprecation-110285-1784678400:

======================================================
Deprecation: #110285 - DataHandler->setCorrelationId()
======================================================

See :issue:`110285`

Description
===========

The method
:php:`\TYPO3\CMS\Core\DataHandling\DataHandler->setCorrelationId()` has been
marked as deprecated and will be removed in TYPO3 v16.0.

The correlation id of a DataHandler operation can now be handed over directly
to :php:`DataHandler->start()` as fifth argument. This also ensures that the
correlation id is passed on to internally spawned sub instances of the
DataHandler, so all record history entries of one logical operation share the
same correlation scope. Setting the correlation id via the setter after
:php:`start()` did not propagate it to sub instances and is therefore
superseded.

Impact
======

Calling :php:`DataHandler->setCorrelationId()` triggers a PHP
:php:`E_USER_DEPRECATED` error.

The extension scanner detects usages of the deprecated method as weak match.

Affected installations
======================

All installations with custom extensions calling
:php:`DataHandler->setCorrelationId()`, usually to group the record history
entries of one logical operation. This is a rarely used API method.

Migration
=========

Pass the :php:`\TYPO3\CMS\Core\DataHandling\Model\CorrelationId` instance
directly to :php:`DataHandler->start()` instead:

.. code-block:: php

    // Before
    $dataHandler->start($dataMap, $commandMap);
    $dataHandler->setCorrelationId($myCorrelationId);
    $dataHandler->process_datamap();

    // After
    $dataHandler->start($dataMap, $commandMap, correlationId: $myCorrelationId);
    $dataHandler->process_datamap();

.. index:: PHP-API, FullyScanned, ext:core
