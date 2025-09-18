..  include:: /Includes.rst.txt

..  _deprecation-107537-1760305681:

================================================
Deprecation: #107537 - FilePathSanitizer Service
================================================

See :issue:`107537`

Description
===========

:php:`TYPO3\CMS\Frontend\Resource\FilePathSanitizer` was introduced as a
service class to replace the logic that was previously part of `$TSFE->tmpl`.

The goal of this class was to validate and manipulate given strings to be used for URL
generation later in the process.

This class and its functionality is superseded by the **System Resource API**.

Impact
======

All installations using the `FilePathSanitizer`
will trigger a deprecation notice when the class is instantiated.

Otherwise this class continue to work as is, until removed in TYPO3 v15.0.

Affected installations
======================

TYPO3 installations with custom extensions/code that instantiates `FilePathSanitizer`.
The extension scanner will report any usage as strong match.

Migration
=========

Use the **System Resource API** instead.

Before:

..  code-block:: php
    :caption: MyClass

    public function __construct(
        private readonly FilePathSanitizer $pathSanitizer,
    ) {}

    public function renderUrl(string $someString, ServerRequestInterface $request): string
    {
        $pathRelativeToPublicDir = $this->pathSanitizer->sanitize($someString);
        return $this->codeThatDoesDetectTheCorrectUrlPrefix() . $pathRelativeToPublicDir;
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


..  index:: PHP-API, FullyScanned, ext:frontend
