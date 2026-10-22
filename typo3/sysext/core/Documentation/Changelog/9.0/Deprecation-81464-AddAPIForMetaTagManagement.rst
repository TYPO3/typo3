.. include:: /Includes.rst.txt

.. _deprecation-81464:

=====================================================
Deprecation: #81464 - Add API for meta tag management
=====================================================

See :issue:`81464`

Description
===========

The following methods have been marked as deprecated and should no longer be used.

* :php-short:`\TYPO3\CMS\Core\Page\PageRenderer->addMetaTag()`
* :php-short:`\TYPO3\CMS\Backend\Template\DocumentTemplate->xUaCompatible`

It has been replaced by the method :php-short:`\TYPO3\CMS\Core\Page\PageRenderer->setMetaTag()`.

.. code-block:: php

   $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
   // has meta tag been set already?
   $previouslySetMetaTag = $pageRenderer->getMetaTag('property', 'og:title');
   // take some decision here
   $pageRenderer->setMetaTag('property', 'og:title', 'My amazing title');


Impact
======

Extensions calling :php-short:`\TYPO3\CMS\Core\Page\PageRenderer->addMetaTag()` or :php-short:`\TYPO3\CMS\Backend\Template\DocumentTemplate->xUaCompatible` will trigger a
deprecation warning.


Affected Installations
======================

All instances using extensions that call :php-short:`\TYPO3\CMS\Core\Page\PageRenderer->addMetaTag()` or :php-short:`\TYPO3\CMS\Backend\Template\DocumentTemplate->xUaCompatible`.


Migration
=========

Migrate code to use :php-short:`\TYPO3\CMS\Core\Page\PageRenderer->setMetaTag($type, $name, $content)` instead.

.. index:: PHP-API, FullyScanned, Frontend
