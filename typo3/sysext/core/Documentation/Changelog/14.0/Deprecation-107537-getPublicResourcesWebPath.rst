.. include:: /Includes.rst.txt

.. _deprecation-107537-1761162068:

===================================================================================
Deprecation: #107537 - TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath
===================================================================================

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

If the code can not be easily refactored to receive a request (which is recommended),
it is fine to pass `null` as request, which instructs the API to check for
a global request and use it if it exists. If no request can be obtained, no absolute URLs
can be created with the new API.

..  index:: PHP-API, FullyScanned, ext:core
