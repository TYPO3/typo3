.. include:: /Includes.rst.txt

======================================
Deprecation: #90421 - DocumentTemplate
======================================

See :issue:`90421`

Description
===========

The PHP class :php:`TYPO3\CMS\Backend\Template\DocumentTemplate`,
also available as :php:`$GLOBALS['TBE_TEMPLATE']` until TYPO3 v10.0
served as a basis to render backend modules or HTML-based output
in TYPO3 Backend.

Since TYPO3 v7, the new API via php:`ModuleTemplate` can be used instead. The :php:`DocumentTemplate` class has been marked as deprecated.


Impact
======

Instantiating the :php:`DocumentTemplate` class will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with third-party extensions adding backend modules using the DocumentTemplate API.
These can typically be identified by extensions that "worked" but somehow looked ugly since TYPO3 v7 due to CSS and HTML changes.


Migration
=========

Use ModuleTemplate API instead, which can be built like this in a typical non-Extbase Backend controller (e.g. in an action such as "overviewAction"):

.. code-block:: php

   $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
   $content = $this->getHtmlContentFromMyModule();
   $moduleTemplate->setTitle('My module');
   $moduleTemplate->setContent($content);
   return $this->responseFactory->createResponse()
       ->withHeader('Content-Type', 'text/html; charset=utf-8')
       ->withBody($this->streamFactory->createStream($moduleTemplate->renderContent()));


.. index:: Backend, PHP-API, FullyScanned, ext:backend
