..  include:: /Includes.rst.txt

..  _deprecation-107783-1760945127:

==========================================================================================
Deprecation: #107783 - Registration of Metadata Extractors via `registerExtractionService`
==========================================================================================

See :issue:`107783`

Description
===========

Registration of Metadata extractors will happen automatically when the required interface
:php:`TYPO3\CMS\Core\Resource\Index\ExtractorInterface` is implemented by a class.
No further registration is necessary.

The method :php:`TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::registerExtractionService()`
has been marked as deprecated, the call in :file:`ext_localconf.php` can be removed without substitution.


Impact
======

Metadata extractors can be registered with less overhead.
Using the method mentioned above will trigger a PHP deprecation warning. The method
will be removed in TYPO3 v15.0. The extension scanner will report usages.


Affected installations
======================

Extensions that register Metadata extractor classes and register them via the mentioned method in :file:`ext_localconf.php`.


Migration
=========

Remove the manual registration from :file:`ext_localconf.php`:

..  code-block:: diff
    :caption: EXT:my_ext/ext_localconf.php

    - $extractorRegistry = GeneralUtility::makeInstance(ExtractorRegistry::class);
    - $extractorRegistry->registerExtractionService(MyExtractor::class);

Since custom extractors already implement the required interface, no further change is required
inside the extractor class itself.

..  index:: FAL, PHP-API, FullyScanned, ext:core
