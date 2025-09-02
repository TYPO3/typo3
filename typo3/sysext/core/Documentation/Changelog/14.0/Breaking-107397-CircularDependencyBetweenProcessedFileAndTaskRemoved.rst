..  include:: /Includes.rst.txt

..  _breaking-107397-1756543200:

==============================================================================
Breaking: #107397 - Circular dependency between ProcessedFile and Task removed
==============================================================================

See :issue:`107397`

Description
===========

The circular dependency between :php:`ProcessedFile` and File Processing Task
classes has been resolved to improve the architecture and maintainability
of the file processing system.

The following changes have been made to :php:`ProcessedFile`:

- The public method :php:`getTask()` has been removed
- The public method :php:`generateProcessedFileNameWithoutExtension()` has been removed

The following changes have been made to :php:`ProcessedFileRepository`:

- Method :php:`add()` now requires a :php:`TaskInterface` parameter
- Method :php:`update()` now requires a :php:`TaskInterface` parameter

The checksum validation logic has been moved
from :php:`ProcessedFile` to :php:`AbstractTask`

Impact
======

Any code that calls the following methods will cause PHP fatal errors:

- :php:`ProcessedFile->getTask()`
- :php:`ProcessedFile->generateProcessedFileNameWithoutExtension()`

Any code that calls :php:`ProcessedFileRepository->add()` or
:php:`ProcessedFileRepository->update()` without the new :php:`TaskInterface`
parameter will cause PHP fatal errors.

Code that relied on :php:`ProcessedFile` objects having Task objects available
internally will no longer work, as Task objects are now created externally
by :php:`FileProcessingService` when needed.

Affected installations
======================

Installations with custom file processing extensions or custom Task
implementations that directly interact with the :php:`ProcessedFile->getTask()`
method are affected. The extension scanner will report any usage of
:php:`ProcessedFile->getTask()` and :php:`ProcessedFile->generateProcessedFileNameWithoutExtension()`
as weak match.

Extensions that manually call :php:`ProcessedFileRepository->add()` or
:php:`ProcessedFileRepository->update()` are also affected. The extension
scanner will not report any usage of these methods due to too many weak matches.

Migration
=========

Replace calls to :php:`ProcessedFile->getTask()` with direct creation of Task
objects through the :php:`TaskTypeRegistry`:

..  code-block:: php

    // Before
    $task = $processedFile->getTask();

    // After
    $taskTypeRegistry = GeneralUtility::makeInstance(TaskTypeRegistry::class);
    $task = $taskTypeRegistry->getTaskForType(
        $processedFile->getTaskType(),
        $processedFile,
        $processedFile->getProcessingConfiguration()
    );

It is recommended to implement your own alternative to
:php:`ProcessedFile->generateProcessedFileNameWithoutExtension()`:

Update calls to :php:`ProcessedFileRepository->add()` and
:php:`ProcessedFileRepository->update()`:

..  code-block:: php

    // Before
    $processedFileRepository->add($processedFile);
    $processedFileRepository->update($processedFile);

    // After
    $task = $taskTypeRegistry->getTaskForType(
        $processedFile->getTaskType(),
        $processedFile,
        $processedFile->getProcessingConfiguration()
    );
    $processedFileRepository->add($processedFile, $task);
    $processedFileRepository->update($processedFile, $task);

.. index:: PHP-API, PartiallyScanned
