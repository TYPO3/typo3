.. include:: /Includes.rst.txt

.. _deprecation-100597-1681480956:

================================================================================================
Deprecation: #100597 - BackendUtility methods getThumbnailUrl() and getLinkToDataHandlerAction()
================================================================================================

See :issue:`100597`

Description
===========

The methods :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getThumbnailUrl()`
and :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getLinkToDataHandlerAction()`
have been marked as deprecated.


Impact
======

Calling those methods will trigger a PHP deprecation level log warning.


Affected installations
======================

TYPO3 installations with custom extensions using those methods. The extension
scanner will report usages as strong match.


Migration
=========

Instead of calling :php:`BackendUtility::getThumbnailUrl()`, inject and use
the :php:`\TYPO3\CMS\Core\Resource\ResourceFactory` directly:

.. code-block:: php

    // before
    $url = BackendUtility::getThumbnailUrl(2004, [
        'width' => 20,
        'height' => 13,
        '_context' => ProcessedFile::CONTEXT_IMAGEPREVIEW
    ]);

    // after
    $url = $this->resourceFactory
        ->getFileObject(2004)
        ->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, ['width' => 20, 'height' => 13])
        ->getPublicUrl();

Instead of calling :php:`BackendUtility::getLinkToDataHandlerAction()`, inject
and use the :php:`\TYPO3\CMS\Backend\Routing\UriBuilder` directly:

..  code-block:: php

    // before
    $url = BackendUtility::getLinkToDataHandlerAction(
        '&cmd[pages][123][localize]=10',
        (string)$uriBuilder->buildUriFromRoute('some_route')
    );

    // after
    $url = (string)$this->uriBuilder->buildUriFromRoute(
        'tce_db',
        [
            'cmd' => [
                'pages' => [
                    123 => [
                        'localize' => 10,
                    ],
                ],
            ],
            'redirect' => (string)$uriBuilder->buildUriFromRoute('some_route'),
        ]
    );

In case the second paramter `$redirectUrl` was omitted,
:php:`getLinkToDataHandlerAction` automatically used the current request URI
as the return URL. In case you relied on this, make sure the `redirect`
parameter is set to :php:`$request->getAttribute('normalizedParams')->getRequestUri()`.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
