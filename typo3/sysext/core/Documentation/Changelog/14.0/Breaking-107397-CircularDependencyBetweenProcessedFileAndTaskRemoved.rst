..  include:: /Includes.rst.txt

..  _breaking-107397-1756543200:

==============================================================================
Breaking: #107397 - Circular dependency between ProcessedFile and Task removed
==============================================================================

See :issue:`107397`

Description
===========

The circular dependency between
:php:`\TYPO3\CMS\Core\Resource\ProcessedFile` and File Processing Task
classes has been resolved to improve the architecture and maintainability of
the File Abstraction Layer (FAL) processing system.

The following changes have been made to
:php-short:`\TYPO3\CMS\Core\Resource\ProcessedFile`:

- The public method :php:`getTask()` has been removed.
- The public method :php:`generateProcessedFileNameWithoutExtension()` has been
  removed.

The following changes have been made to
:php-short:`\TYPO3\CMS\Core\Resource\ProcessedFileRepository`:

- Method :php:`add()` now requires a :php:`TaskInterface` parameter.
- Method :php:`update()` now requires a :php:`TaskInterface` parameter.

Additionally, the checksum validation logic has been moved from
:php-short:`\TYPO3\CMS\Core\Resource\ProcessedFile` to
:php-short:`\TYPO3\CMS\Core\Resource\Processing\AbstractTask`.

Impact
======

Any code that calls the following methods will cause PHP fatal errors:

-   :php:`ProcessedFile->getTask()`
-   :php:`ProcessedFile->generateProcessedFileNameWithoutExtension()`

Any code that calls :php:`ProcessedFileRepository->add()` or
:php:`ProcessedFileRepository->update()` without the new
:php:`TaskInterface` parameter will cause PHP fatal errors.

Code that relied on ::php-short:`\TYPO3\CMS\Core\Resource\ProcessedFile` objects
having Task objects available internally will no longer work, as Task objects
are now created externally by
:php-short:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService` when
needed.

Affected installations
======================

Installations with custom file processing extensions or custom Task
implementations that directly interact with the
:php:`ProcessedFile->getTask()` method are affected. The extension scanner will
report any usage of :php:`ProcessedFile->getTask()` and
:php:`ProcessedFile->generateProcessedFileNameWithoutExtension()` as weak
matches.

Extensions that manually call :php:`ProcessedFileRepository->add()` or
:php:`ProcessedFileRepository->update()` are also affected. The extension
scanner will not report usages of these methods due to too many weak matches.

Migration
=========

Replace calls to :php:`ProcessedFile->getTask()` with direct creation of Task
objects through the :php-short:`\TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry`:

**Before:**

..  code-block:: php

    $task = $processedFile->getTask();

**After:**

..  code-block:: php

    use TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $taskTypeRegistry = GeneralUtility::makeInstance(TaskTypeRegistry::class);
    $task = $taskTypeRegistry->getTaskForType(
        $processedFile->getTaskIdentifier(),
        $processedFile,
        $processedFile->getProcessingConfiguration()
    );

It is recommended to implement your own alternative to
:php:`ProcessedFile->generateProcessedFileNameWithoutExtension()` if similar
logic is still needed.

Update calls to :php:`ProcessedFileRepository->add()` and
:php:`ProcessedFileRepository->update()` to include the new
:php-short:`\TYPO3\CMS\Core\Resource\Processing\TaskInterface` parameter:

..  code-block:: php
    :caption: Updated calls to ProcessedFileRepository

    use TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry;
    use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // Before
    $processedFileRepository->add($processedFile);
    $processedFileRepository->update($processedFile);

    // After
    $taskTypeRegistry = GeneralUtility::makeInstance(TaskTypeRegistry::class);
    $task = $taskTypeRegistry->getTaskForType(
        $processedFile->getTaskIdentifier(),
        $processedFile,
        $processedFile->getProcessingConfiguration()
    );

    $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
    $processedFileRepository->add($processedFile, $task);
    $processedFileRepository->update($processedFile, $task);

..  index:: PHP-API, PartiallyScanned
