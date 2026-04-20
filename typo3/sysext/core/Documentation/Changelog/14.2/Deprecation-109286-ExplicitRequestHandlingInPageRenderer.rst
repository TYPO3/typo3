..  include:: /Includes.rst.txt

..  _deprecation-109286-1773844395:

================================================================
Deprecation: #109286 - Explicit request handling in PageRenderer
================================================================

See :issue:`109286`

Description
===========

Since TYPO3 v14.2 some methods of class :php:`TYPO3\CMS\Core\Page\PageRenderer`
require an instance of :php-short:`\Psr\Http\Message\ServerRequestInterface` to
be passed explicitly :

setLanguage()
-------------

*   Old: :php:`PageRenderer->setLanguage(Locale $locale, ?ServerRequestInterface $request = null)`
*   TYPO3 v14.2: :php:`PageRenderer->setLanguage(Locale $locale, ?ServerRequestInterface $request = null)`
*   TYPO3 v15: :php:`PageRenderer->setLanguage(Locale $locale, ServerRequestInterface $request)`

setDocType()
------------

*   Old: :php:`PageRenderer->setDocType(DocType $docType)`
*   TYPO3 v14.2: :php:`PageRenderer->setDocType(DocType $docType, ?ServerRequestInterface $request = null)`
*   TYPO3 v15: :php:`PageRenderer->setDocType(DocType $docType, ServerRequestInterface $request)`

render()
--------

*   Old: :php:`PageRenderer->render()`
*   TYPO3 v14.2: :php:`PageRenderer->render(?ServerRequestInterface $request = null)`
*   TYPO3 v15: :php:`PageRenderer->render(ServerRequestInterface $request)`

renderResponse()
----------------

*   Old: :php:`PageRenderer->renderResponse(int $code = 200, string $reasonPhrase = '')`
*   TYPO3 v14.2: :php:`PageRenderer->render(ServerRequestInterface|int $requestOrCode = 200, int|string $codeOrReasonPhrase = '', string $reasonPhrase = '')`
*   TYPO3 v15: :php:`PageRenderer->render(ServerRequestInterface $request, int $code = 200, string $reasonPhrase = '')`


Impact
======

Request dependencies within
:php-short:`TYPO3\CMS\Core\Page\PageRenderer` are no longer implicit via
:php:`$GLOBALS['TYPO3_REQUEST']` and must now be passed explicitly. Not
passing a request to the methods listed above will trigger a deprecation-level
log entry in TYPO3 v14 and will result in a fatal PHP error in TYPO3 v15.

Affected installations
======================

:php-short:`TYPO3\CMS\Core\Page\PageRenderer` is a low-level Core class. Many
extensions use higher-level APIs and are therefore not directly affected by
this change.

Migration
=========

Adapt method calls to pass the
:php-short:`\Psr\Http\Message\ServerRequestInterface` object explicitly.

..  index:: Backend, Frontend, PHP-API, NotScanned, ext:core
