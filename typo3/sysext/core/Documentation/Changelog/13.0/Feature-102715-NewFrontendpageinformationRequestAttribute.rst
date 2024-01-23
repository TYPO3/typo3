.. include:: /Includes.rst.txt

.. _feature-102715-1703261072:

==================================================================
Feature: #102715 - New frontend.page.information request attribute
==================================================================

See :issue:`102715`

Description
===========

TYPO3 v13 introduces the new frontend-related PSR-7 request attribute :php:`frontend.page.information`
implemented by class :php:`\TYPO3\CMS\Frontend\Page\PageInformation`. The object aims to replace
various page related properties of :php:`\TYPO3\CMS\Frontend\Controller\TyposcriptFrontendController`.

Note the class is currently still marked as experimental. Extension authors are however encouraged
to use information from this request attribute instead of the :php:`TyposcriptFrontendController`
properties already: TYPO3 Core v13 will try to not break especially the getters / properties not
marked as :php:`@internal`.


Impact
======

There are three properties in :php:`TyposcriptFrontendController` frequently used by extensions,
which are now modeled in :php:`\TYPO3\CMS\Frontend\Page\PageInformation`. The attribute is
attached to the PSR-7 frontend request by middleware :php:`TypoScriptFrontendInitialization`,
middlewares below can rely on existence of that attribute. Examples:

.. code-block:: php

    $pageInformation = $request->getAttribute('frontend.page.information');
    // Formerly $tsfe->id
    $id = $pageInformation->getId();
    // Formerly $tsfe->page
    $page = $pageInformation->getPageRecord();
    // Formerly $tsfe->rootLine
    $rootLine = $pageInformation->getRootLine();


.. index:: Frontend, PHP-API, ext:frontend
