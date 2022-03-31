.. include:: /Includes.rst.txt

==============================================================================
Feature: #89292 - Add support for RecordHistory correlationId's to DataHandler
==============================================================================

See :issue:`89292`

Description
===========

With :issue:`89143` a new feature for correlation ids in :php:`\TYPO3\CMS\Backend\History\RecordHistory` was introduced.
:php:`DataHandler` now also supports this feature by setting the :php:`$correlationId` with its instance.

.. code-block:: php

   $correlationId = CorrelationId::forSubject(
       md5(StringUtility::getUniqueId('slug_'))
   );
   $data['pages'][$uid]['slug'] = $newSlug;
   // create new DataHandler instance
   $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
   $dataHandler->start($data, []);
   // DataHandler::start assigns internal correlation id scope
   // which will be overridden in this example by the next line
   $dataHandler->setCorrelationId($correlationId);
   // actually process and persist data
   $dataHandler->process_datamap();

After this DataHandler operation, the created RecordHistory entry contains the :php:`$correlationId`.

:php:`CorrelationId` model requires mandatory :php:`$subject` and allows optional :php:`$aspects` which
can be serialized into string like e.g. `0400$12ae0b042a5d75e3f2744f4b3faf8068/5d8e6e70/slug`

* `0400$` is a flag prefix containing an internal version number for possible schema validations
* `12ae0b042a5d75e3f2744f4b3faf8068` is a unique subject
* `/5d8e6e70/slug` are aspects, separated by slashes

.. index:: Backend, Database, PHP-API, ext:core
