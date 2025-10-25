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

..  note::

    The code should always be refactored to receive a request, which must then be passed to the API.
    If that is not possible due to restrictions in TYPO3 legacy API (e.g. Events or Hooks not passing the
    request, but an URL must be generated in the listener), null can be passed.
    Passing null instructs the API to check for :php:`$GLOBALS['TYPO3_REQUEST']` and use it (if it exists).
    Be aware, though, that this global variable will deprecated and removed eventually.

    On CLI, :php:`$GLOBALS['TYPO3_REQUEST']` is never available, neither is there a request.
    In that case, passing `null` to the API has the consequence that no absolute URLs (containing host
    and scheme) can be created. If you need absolute URLs based on a certain site on CLI,
    a request must be constructed accordingly and passed to the API.
    For more information about this and how to get/construct/mock the request object,
    see `TYPO3 request object <https://docs.typo3.org/permalink/t3coreapi:typo3-request>`_.

..  index:: PHP-API, FullyScanned, ext:core
