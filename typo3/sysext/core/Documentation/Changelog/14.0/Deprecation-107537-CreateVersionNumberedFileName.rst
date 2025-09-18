..  include:: /Includes.rst.txt

..  _deprecation-107537-1760337101:

====================================================================
Deprecation: #107537 - GeneralUtility::createVersionNumberedFilename
====================================================================

See :issue:`107537`

Description
===========

`GeneralUtility::createVersionNumberedFilename` adds cache busting to a file
URL, when called in a certain and correct order with other legacy API
methods to create URLs from system resources.

This class and its functionality is superseded by the **System Resource API**.

Impact
======

Calling this method will trigger a PHP deprecation warning. The method will
continue to work as is, until it is removed in TYPO3 v15.0.

Affected installations
======================

TYPO3 installations with custom extensions/code that directly call this
deprecated method:

*   :php:`GeneralUtility::createVersionNumberedFilename`


Migration
=========

Use the **System Resource API** instead.

Before:

..  code-block:: php
    :caption: MyClass

    public function renderUrl(string $file): string
    {
        $file = GeneralUtility::getFileAbsFileName($file);
        $partialUrl = GeneralUtility::createVersionNumberedFilename($file);
        return PathUtility::getAbsoluteWebPath($partialUrl);
    }

After:

..  code-block:: php
    :caption: MyClass

    public function __construct(
        private readonly SystemResourceFactory $systemResourceFactory,
        private readonly SystemResourcePublisherInterface $resourcePublisher,
    ) {}

    public function renderUrl(string $resourceIdentifier, ServerRequestInterface $request): string
    {
        $resource = $this->systemResourceFactory->createPublicResource($resourceIdentifier);
        return (string)$this->resourcePublisher->generateUri(
            $resource,
            $request,
            new UriGenerationOptions(absoluteUri: true),
        );
    }


..  index:: PHP-API, FullyScanned, ext:core
