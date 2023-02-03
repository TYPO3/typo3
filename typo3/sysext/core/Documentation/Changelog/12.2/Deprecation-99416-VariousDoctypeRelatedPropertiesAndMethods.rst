.. include:: /Includes.rst.txt

.. _deprecation-99416-1671746489:

====================================================================
Deprecation: #99416 - Various doctype related properties and methods
====================================================================

See :issue:`99416`

Description
===========

Due to the introduction of a unified definition of the DocType that should render
HTML, XML or XHTML-compliant content either in TYPO3 frontend rendering or
backend rendering, various methods and properties have been marked as
deprecated, as they are superfluous now:

*   :php:`\TYPO3\CMS\Core\Page\PageRenderer->setRenderXhtml()`
*   :php:`\TYPO3\CMS\Core\Page\PageRenderer->getRenderXhtml()`
*   :php:`\TYPO3\CMS\Core\Page\PageRenderer->setMetaCharsetTag()`
*   :php:`\TYPO3\CMS\Core\Page\PageRenderer->getMetaCharsetTag()`
*   :php:`\TYPO3\CMS\Core\Page\PageRenderer->setCharSet()`
*   :php:`\TYPO3\CMS\Core\Page\PageRenderer->getCharSet()`
*   :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->xhtmlDoctype`
*   :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->xhtmlVersion`


Impact
======

Calling one of the methods or accessing / writing one of the properties mentioned
will trigger a PHP deprecation message.


Affected installations
======================

TYPO3 installations with custom extensions reading or writing these properties or
methods directly in PHP, which is unlikely.


Migration
=========

Use :php:`PageRenderer->setDocType()` to manipulate the output in
a programmatic way, or use :php:`PageRenderer->getDocType()` to read the
current doctype — for example "is the current page HTML5 compliant".

Various TypoScript properties will instruct the :php:`PageRenderer` as before,
there is no need to use other configuration options. However, it is recommended to use
:typoscript:`config.doctype` in favor of :typoscript:`config.xhtmlDoctype` in
TypoScript as it considers more possible options.

.. index:: Frontend, TypoScript, FullyScanned, ext:frontend
