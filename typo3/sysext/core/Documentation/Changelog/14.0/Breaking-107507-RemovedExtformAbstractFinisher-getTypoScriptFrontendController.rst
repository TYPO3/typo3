..  include:: /Includes.rst.txt

..  _breaking-107507-1758376090:

========================================================================================
Breaking: #107507 - Removed EXT:form AbstractFinisher->getTypoScriptFrontendController()
========================================================================================

See :issue:`107507`

Description
===========

The method
:php:`\TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher->getTypoScriptFrontendController()`
has been removed.

Since the entire :php-short:`TypoScriptFrontendController` class is being phased out,
this abstract helper method has been removed as part of that cleanup.

Impact
======

Calling this method in a custom EXT:form finisher will result in a fatal PHP
error.

Affected installations
======================

TYPO3 instances using EXT:form with custom finishers that call this method are
affected. The extension scanner is configured to detect such usages.

Migration
=========

Migration depends on what the finisher previously did with the returned class
instance. The :php:`TypoScriptFrontendController` properties and helper methods
have been modernized, with most data now available as request attributes.

For example, accessing the :php:`cObj` property can be replaced like this:

..  code-block:: php

    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

    $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
    $cObj->setRequest($request);
    $cObj->start(
        $request->getAttribute('frontend.page.information')->getPageRecord(),
        'pages'
    );

..  index:: PHP-API, FullyScanned, ext:form
