.. include:: /Includes.rst.txt

.. _breaking-110702-1789915156:

==========================================================
Breaking: #110702 - Widened FAL processing task interfaces
==========================================================

See :issue:`110702`

Description
===========

TYPO3's File Abstraction Layer (FAL) processing pipeline - the
:php-short:`\TYPO3\CMS\Core\Resource\Processing\TaskInterface`/:php:`AbstractTask`/:php:`ProcessorInterface`/
:php-short:`\TYPO3\CMS\Core\Resource\Processing\ProcessorRegistry`/:php-short:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService` classes that resize,
crop and convert images - has so far only ever operated on FAL files
(:php:`\TYPO3\CMS\Core\Resource\File` and
:php:`\TYPO3\CMS\Core\Resource\ProcessedFile`). The same pipeline now also
processes resources that are not backed by FAL - most commonly a system
resource shipped with a package and referenced via an :php:`EXT:`/:php:`PKG:`
identifier - by routing a :php-short:`\TYPO3\CMS\Core\Resource\ProcessedFile`, or the new
:php:`\TYPO3\CMS\Core\Resource\ProcessedResource`, through the same task/
processor machinery (see :ref:`feature-110702-1789927725`). Several
signatures widened for this:

- :php-short:`\TYPO3\CMS\Core\Resource\Processing\TaskInterface::getSourceFile()` now returns
  :php:`\TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface`
  instead of :php:`\TYPO3\CMS\Core\Resource\File`.
- :php-short:`\TYPO3\CMS\Core\Resource\Processing\TaskInterface::getTargetFile()` now returns the new
  :php:`\TYPO3\CMS\Core\Resource\ProcessedResourceInterface` instead of
  :php:`\TYPO3\CMS\Core\Resource\ProcessedFile`. :php-short:`\TYPO3\CMS\Core\Resource\ProcessedFile` now
  implements this new interface, alongside the new
  :php-short:`\TYPO3\CMS\Core\Resource\ProcessedResource` for the non-FAL case.
- :php:`AbstractTask::__construct()` and
  :php-short:`\TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry::getTaskForType()` now take a
  :php-short:`\TYPO3\CMS\Core\Resource\ProcessedResourceInterface` instead of a :php-short:`\TYPO3\CMS\Core\Resource\ProcessedFile`, and
  the :php:`AbstractTask::$sourceFile` property is now typed
  :php-short:`\TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface` instead of :php-short:`\TYPO3\CMS\Core\Resource\File`. A custom task class
  registered for a task type is constructed with a :php-short:`\TYPO3\CMS\Core\Resource\ProcessedResource`
  when the source is a system resource.
- :php-short:`\TYPO3\CMS\Core\Resource\Processing\ProcessorRegistry` no longer offers a task whose source is not a FAL
  :php-short:`\TYPO3\CMS\Core\Resource\File` to a processor unless that processor implements the new,
  opt-in :php:`\TYPO3\CMS\Core\Resource\Processing\ResourceAwareProcessorInterface`
  marker. TYPO3 Core's :php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor` and :php-short:`\TYPO3\CMS\Core\Resource\Processing\SvgImageProcessor`
  now implement it and handle a non-FAL source for the
  :php:`Image.CropScaleMask` task; no other Core processor does. As with any
  interface, a subclass inherits it: a custom processor extending
  :php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor` or :php-short:`\TYPO3\CMS\Core\Resource\Processing\SvgImageProcessor` is offered non-FAL
  tasks as well.
- :php-short:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService` has a new, last constructor argument
  :php:`\TYPO3\CMS\Core\Resource\ProcessedResourceCache`.
- :php-short:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService::processFile()` now accepts
  :php:`File|FileReference|SystemResourceInterface` (was
  :php:`File|FileReference`), returns :php:`ProcessedFile|ProcessedResource`
  (was :php-short:`\TYPO3\CMS\Core\Resource\ProcessedFile`), and its :php:`$driver` parameter is now
  nullable - a non-FAL resource is never fetched through a
  :php-short:`\TYPO3\CMS\Core\Resource\ResourceStorage` driver in the first place, so there is none to pass.
- :php-short:`\TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent`/:php-short:`\TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent` widen the
  same way: :php:`getDriver()` now returns :php:`?DriverInterface`,
  :php:`getFile()` returns :php:`FileInterface|SystemResourceInterface`, and
  :php:`getProcessedFile()`/:php:`setProcessedFile()` use
  :php-short:`\TYPO3\CMS\Core\Resource\ProcessedResourceInterface`.
- :php:`\TYPO3\CMS\Core\Imaging\ImageResource::getOriginalFile()` now
  returns :php:`File|SystemResourceInterface|null` (was :php:`?File`), and
  :php-short:`\TYPO3\CMS\Core\Imaging\ImageResource::getProcessedFile()` now returns
  :php:`?ProcessedResourceInterface` (was :php:`?ProcessedFile`). The
  corresponding :php:`with*()` methods and the constructor accept the same
  wider types. An image resource of a processed system resource carries the
  system resource and its :php-short:`\TYPO3\CMS\Core\Resource\ProcessedResource`.

The URLs of :php:`EXT:`/:php:`PKG:` images rendered through image
processing change as well:

- A processed variant is no longer stored in
  :file:`typo3temp/assets/_processed_/` as
  :file:`csm_<name>_<hash>.<extension>`, but in
  :file:`typo3temp/assets/images/system/<hash>/<hash>-<hash>.<extension>`.
- An image that does not need processing is linked by its own public URL,
  as generated by the system resource publisher - including its cache
  busting query string, for example :file:`logo.png?1791262813`.
- The link generated by :typoscript:`imageLinkWrap` for such an image passes
  the system resource identifier as :php:`file` parameter to the
  :php:`tx_cms_showpic` eID, instead of the uid of a :sql:`sys_file` record.

Impact
======

Custom :php:`ProcessorInterface`/:php-short:`\TYPO3\CMS\Core\Resource\Processing\TaskInterface`/:php:`AbstractTask`
implementations, or PSR-14 listeners of the two events above, that use
:php:`getSourceFile()`/:php:`getTargetFile()`/:php:`getFile()`/
:php:`getProcessedFile()`/:php:`getDriver()` as a concrete
:php-short:`\TYPO3\CMS\Core\Resource\File`/:php-short:`\TYPO3\CMS\Core\Resource\ProcessedFile`/:php-short:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface` without a type check
- for example calling :php:`getUid()`, :php:`getForLocalProcessing()`,
:php:`getStorage()` or any :php-short:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface` method directly - will
fail static analysis against the wider declared type, and can now
genuinely receive a system resource, or a :php:`null` driver, at runtime:
a template or content element that references an :php:`EXT:`/:php:`PKG:`
image is processed through this same pipeline starting with this release.

Custom task classes that type-hint the constructor argument as
:php-short:`\TYPO3\CMS\Core\Resource\ProcessedFile`, or assign a :php-short:`\TYPO3\CMS\Core\Resource\File` to :php:`$sourceFile`, fail
with a PHP fatal error. Code that instantiates
:php-short:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService` manually, or extends it with its own
constructor, fails with an :php:`ArgumentCountError`. Code relying on
:php-short:`\TYPO3\CMS\Core\Imaging\ImageResource::getOriginalFile()`/:php:`getProcessedFile()` returning a
FAL object fails for an :php:`EXT:`/:php:`PKG:` image.

Tests, caches, CDN or proxy rules and other code that relied on the
previous URL pattern of processed :php:`EXT:`/:php:`PKG:` images no longer
match.

Affected installations
=======================

Installations with extensions that implement :php:`ProcessorInterface`
directly, subclass :php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor`/:php-short:`\TYPO3\CMS\Core\Resource\Processing\SvgImageProcessor`,
:php:`AbstractTask` or :php-short:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService`, listen to
:php-short:`\TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent`/:php-short:`\TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent`, or use
:php-short:`\TYPO3\CMS\Core\Imaging\ImageResource::getOriginalFile()`/:php:`getProcessedFile()`, and rely
on the narrower pre-existing types without narrowing themselves.

Installations that depend on the URL of a processed :php:`EXT:`/:php:`PKG:`
image.

Migration
=========

Narrow the type before using :php-short:`\TYPO3\CMS\Core\Resource\File`/:php-short:`\TYPO3\CMS\Core\Resource\ProcessedFile`/
:php-short:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface`-specific members, the same way TYPO3 Core's own
processors do now:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Resource/Processing/MyProcessor.php

    use TYPO3\CMS\Core\Resource\File;

    public function processTask(TaskInterface $task): void
    {
        $sourceFile = $task->getSourceFile();
        if (!$sourceFile instanceof File) {
            throw new \InvalidArgumentException('MyProcessor can only process a FAL File.', 1234567890);
        }
        // $sourceFile is now a concrete File again.
    }

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/MyFileProcessingListener.php

    public function __invoke(AfterFileProcessingEvent $event): void
    {
        $driver = $event->getDriver();
        if ($driver === null) {
            // A non-FAL resource - nothing to do here, or handle it explicitly.
            return;
        }
        // $driver is now a concrete DriverInterface again.
    }

Custom task classes declare the constructor argument as
:php-short:`\TYPO3\CMS\Core\Resource\ProcessedResourceInterface`, the same as :php:`AbstractTask` does.

A subclass of :php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor` or :php-short:`\TYPO3\CMS\Core\Resource\Processing\SvgImageProcessor` that
should only handle FAL files declines other tasks in
:php:`canProcessTask()`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Resource/Processing/MyImageProcessor.php

    use TYPO3\CMS\Core\Resource\File;

    public function canProcessTask(TaskInterface $task): bool
    {
        return $task->getSourceFile() instanceof File
            && parent::canProcessTask($task);
    }

Code that instantiates :php-short:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService` itself retrieves it
through dependency injection instead.

Check :php-short:`\TYPO3\CMS\Core\Imaging\ImageResource::getOriginalFile()`/:php:`getProcessedFile()` for
:php-short:`\TYPO3\CMS\Core\Resource\File`/:php-short:`\TYPO3\CMS\Core\Resource\ProcessedFile` before using FAL-specific methods.

Do not rely on the file name or location of a processed image.

.. index:: FAL, PHP-API, TypoScript, NotScanned, ext:core
