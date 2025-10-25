.. include:: /Includes.rst.txt

.. _deprecation-107537-1761162068:

=====================================================================================
Deprecation: #107537 - `TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath`
=====================================================================================

See :issue:`107537`

Description
===========

The static method :php:`PathUtility::getPublicResourceWebPath($extResource)` was marked internal
since it was introduced. Since there weren't good alternatives to this API, it is not removed
but deprecated first before it will be removed with TYPO3 v15

Impact and affected installations
=================================

TYPO3 installations using :php:`PathUtility::getPublicResourceWebPath($extResource)` will get a
deprecation message for each call of this method.

Migration
=========

Use the **System Resource API** instead.

Before:

..  code-block:: php
    :caption: MyClass

    public function renderUrl(string $extResource): string
    {
        return PathUtility::getPublicResourceWebPath($extResource);
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
