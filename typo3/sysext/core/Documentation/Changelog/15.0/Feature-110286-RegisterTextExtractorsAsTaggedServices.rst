..  include:: /Includes.rst.txt

..  _feature-110286-1784879763:

==============================================================
Feature: #110286 - Register text extractors as tagged services
==============================================================

See :issue:`110286`

Description
===========

Text extractors, used to extract the textual content from files, for
example for search indexing, are now registered as tagged services via
dependency injection instead of the previous programmatic registration
through
:php:`\TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry->registerTextExtractor()`
in :file:`ext_localconf.php`.

A text extractor class implementing
:php:`\TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface` is
registered by adding the new PHP attribute
:php:`\TYPO3\CMS\Core\Attribute\AsTextExtractor` to the class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Resource/TextExtraction/PdfTextExtractor.php

    use TYPO3\CMS\Core\Attribute\AsTextExtractor;
    use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;

    #[AsTextExtractor(priority: 10)]
    final class PdfTextExtractor implements TextExtractorInterface
    {
        // ...
    }

The extractor priority is defined at registration time via the
attribute. Extractors with a higher priority are asked first whether
they can extract text from a given file (:php:`canExtractText()`). The
:php:`PlainTextExtractor` shipped with TYPO3 Core is registered with the
default priority :php:`0`, so any custom extractor using a priority
above :php:`0` takes precedence. The order in which extractors of the
same priority are evaluated is not defined and must not be relied upon;
use distinct priorities if the evaluation order matters.

Alternatively, the service tag :yaml:`fal.text_extractor` can be used
directly in :file:`Configuration/Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Resource\TextExtraction\PdfTextExtractor:
      tags:
        - name: fal.text_extractor
          priority: 10

Impact
======

Registering text extractors as tagged services has the following
benefits over the previous programmatic registration:

-   The registration is resolved once at container compile time instead
    of executing registration code from :file:`ext_localconf.php` on
    every request. Loading and validating the extractor classes per
    request as well as instantiating them at runtime is not necessary
    anymore.

-   Text extractors are proper services now and can use dependency
    injection in their constructor.

-   The extractor priority is declared at the class itself instead of
    depending on the loading order of :file:`ext_localconf.php` files.

-   The registration is validated at container compile time: a service
    tagged as :yaml:`fal.text_extractor` that does not implement
    :php:`TextExtractorInterface` fails the container build with a
    speaking exception, instead of causing errors when text is
    extracted.

Registration in :file:`ext_localconf.php` via
:php:`TextExtractorRegistry->registerTextExtractor()` is not evaluated
anymore, see :ref:`breaking-110286-1784879763` for the upgrade path.

..  index:: FAL, PHP-API, ext:core
