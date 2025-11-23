..  include:: /Includes.rst.txt

..  _breaking-107831-1761307521:

==========================================================
Breaking: #107831 - Removed `TypoScriptFrontendController`
==========================================================

See :issue:`107831`

Description
===========

All remaining properties have been removed from
:php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController`, making the
class a readonly internal service used by the TYPO3 Core only.

The class will be fully removed in a later TYPO3 v14 release.

The following instance access patterns have been removed:

*   `$GLOBALS['TSFE']`
*   `$request->getAttribute('frontend.controller')`
*   `$contentObjectRenderer->getTypoScriptFrontendController()`

All API methods that returned an instance of
:php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` -
usually named :php:`getTypoScriptFrontendController()` or similar - have been
removed as well.

Impact
======

Any remaining direct or indirect usage of
:php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` will
now result in a fatal PHP error.

Affected installations
======================

Extensions that still relied on properties or methods of
:php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` are
affected.

The class was already marked *internal* and *breaking* in TYPO3 v13.

In particular, extensions that used
:php:`AbstractContentObject->getTypoScriptFrontendController()` can now access
the relevant data from the PSR-7 request object, for example:

..  code-block:: php

    $pageInformation = $request->getAttribute('frontend.page.information');

Migration
=========

See :ref:`breaking-102621-1701937690` for a detailed list of removed properties
and their replacements.

As a specific example, old code that added additional header data like this:

..  code-block:: php

    $frontendController = $request->getAttribute('frontend.controller');
    $frontendController->additionalHeaderData[] = $myAdditionalHeaderData;

should now use:

..  code-block:: php

    use TYPO3\CMS\Core\Page\PageRenderer;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
    $pageRenderer->addHeaderData($myAdditionalHeaderData);

The same approach applies to :php:`additionalFooterData`.

..  index:: Frontend, NotScanned, ext:frontend
