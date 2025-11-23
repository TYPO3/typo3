..  include:: /Includes.rst.txt

..  _deprecation-107537-1760305681:

================================================
Deprecation: #107537 - FilePathSanitizer service
================================================

See :issue:`107537`

Description
===========

:php:`\TYPO3\CMS\Frontend\Resource\FilePathSanitizer` was introduced as a
service class to replace the logic that was previously part of
:php:`$TSFE->tmpl`.

The goal of this class was to validate and manipulate given strings to be
used for URL generation later in the process.

This class and its functionality are superseded by the **System Resource API**.

Impact
======

All installations using the
:php-short:`\TYPO3\CMS\Frontend\Resource\FilePathSanitizer` will trigger a
deprecation notice when the class is instantiated.

Otherwise, this class will continue to work as is, until removed in TYPO3
v15.0.

Affected installations
======================

TYPO3 installations with custom extensions or code that instantiate
:php-short:`\TYPO3\CMS\Frontend\Resource\FilePathSanitizer`.

The extension scanner will report any usage as a **strong match**.

Migration
=========

Use the **System Resource API** instead.

Before:

..  code-block:: php
    :caption: MyClass

    use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
    use Psr\Http\Message\ServerRequestInterface;

    public function __construct(
        private readonly FilePathSanitizer $pathSanitizer,
    ) {}

    public function renderUrl(string $someString): string
    {
        $pathRelativeToPublicDir = $this->pathSanitizer->sanitize($someString);
        return $this->codeThatDoesDetectTheCorrectUrlPrefix()
            . $pathRelativeToPublicDir;
    }

After:

..  code-block:: php
    :caption: MyClass

    use TYPO3\CMS\Core\Http\ServerRequestInterface;
    use TYPO3\CMS\Core\Resource\SystemResourceFactory;
    use TYPO3\CMS\Core\Resource\SystemResourcePublisherInterface;
    use TYPO3\CMS\Core\Resource\UriGenerationOptions;

    public function __construct(
        private readonly SystemResourceFactory $systemResourceFactory,
        private readonly SystemResourcePublisherInterface $resourcePublisher,
    ) {}

    public function renderUrl(
        string $resourceIdentifier,
        ServerRequestInterface $request
    ): string {
        $resource = $this->systemResourceFactory->createPublicResource(
            $resourceIdentifier
        );
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

..  index:: PHP-API, FullyScanned, ext:frontend
