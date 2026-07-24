..  include:: /Includes.rst.txt

..  _breaking-110286-1784879763:

=====================================================================
Breaking: #110286 - Text extractor registration and interface changed
=====================================================================

See :issue:`110286`

Description
===========

Text extractors are now registered as tagged services via dependency
injection (see :ref:`feature-110286-1784879763`). This comes with the
following breaking changes:

-   :php:`\TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry->registerTextExtractor()`
    is now a no-op. Calling the method has no effect anymore. The method
    will be removed in TYPO3 v16.0.

-   Extractors registered with the same priority are no longer
    guaranteed to be asked in the order they were added: previously,
    extractors kept the order in which
    :php:`TextExtractorRegistry->registerTextExtractor()` was called from
    :file:`ext_localconf.php`. The order of same-priority tagged services
    is now an implementation detail of the dependency injection container
    and must not be relied upon. Extensions that depend on a specific
    evaluation order between extractors should assign distinct priorities
    instead.

-   The methods of
    :php:`\TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface`
    are now strictly typed: :php:`canExtractText(FileInterface $file): bool`
    and :php:`extractText(FileInterface $file): string`.

-   The method :php:`createTextExtractorInstance()` has been removed
    from :php:`TextExtractorRegistry`, the method
    :php:`getTextExtractorInstances()` has been changed from public to
    protected visibility, and the remaining methods are now strictly
    typed. The public API of the registry is
    :php:`getTextExtractor()`, which returns the first matching
    extractor for a given file.

Impact
======

Text extractors registered via
:php:`TextExtractorRegistry->registerTextExtractor()` in
:file:`ext_localconf.php` are no longer evaluated: no text is extracted
by the custom extractor until it is registered as a tagged service.

Custom extractor classes implementing :php:`TextExtractorInterface`
without the adapted method signatures will cause a fatal PHP error.

Affected installations
======================

All installations with custom extensions registering text extractors
via :php:`TextExtractorRegistry->registerTextExtractor()`, or providing
custom implementations of :php:`TextExtractorInterface`. The extension
scanner reports usages of :php:`registerTextExtractor()` as weak match.

Migration
=========

Remove the :php:`TextExtractorRegistry->registerTextExtractor()` call
from :file:`ext_localconf.php` and add the :php:`#[AsTextExtractor]`
attribute to the extractor class instead. Add the native type
declarations to :php:`canExtractText()` and :php:`extractText()`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Resource/TextExtraction/PdfTextExtractor.php

    use TYPO3\CMS\Core\Attribute\AsTextExtractor;
    use TYPO3\CMS\Core\Resource\FileInterface;
    use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;

    #[AsTextExtractor(priority: 10)]
    final class PdfTextExtractor implements TextExtractorInterface
    {
        public function canExtractText(FileInterface $file): bool
        {
            // ...
        }

        public function extractText(FileInterface $file): string
        {
            // ...
        }
    }

In case the extension supports both TYPO3 v14 and v15, register the
extractor in both ways: the :file:`ext_localconf.php` registration is
evaluated in v14, the attribute in v15. The :php:`bool` and
:php:`string` return type declarations are compatible with both
versions.

Code that called :php:`TextExtractorRegistry->getTextExtractorInstances()`
to inspect all registered extractors should inject
:php:`TextExtractorRegistry` and use :php:`getTextExtractor($file)` to
retrieve the matching extractor for a given file instead.

..  index:: FAL, PHP-API, PartiallyScanned, ext:core
