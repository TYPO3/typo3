.. include:: ../../Includes.txt

==============================================================================
Feature: #89292 - Add support for RecordHistory correlationId's to DataHandler
==============================================================================

See :issue:`89292`

Description
===========

With :issue:`89143` a new feature for correlation ids in RecordHistory was introduced.
The DataHandler now also supports this feature by settings the :php:`$correlationId`
of the DataHandler instance.

.. code-block:: php

   $correlationId = StringUtility::getUniqueId('slug_');
   $data['pages'][$uid]['slug'] = $newSlug;
   $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
   $dataHandler->setCorrelationId($correlationId);
   $dataHandler->start($data, []);
   $dataHandler->process_datamap();

After this DataHandler operation the created RecordHistory entry contains the $correlationId.

.. index:: Backend, Database, PHP-API, ext:core
