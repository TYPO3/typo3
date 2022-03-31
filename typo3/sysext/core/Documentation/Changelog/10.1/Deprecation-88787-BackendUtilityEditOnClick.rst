.. include:: /Includes.rst.txt

=================================================
Deprecation: #88787 - BackendUtility::editOnClick
=================================================

See :issue:`88787`

Description
===========

The method :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick()`
used to generate JavaScript `onclick` targets to
:php:`\TYPO3\CMS\Backend\Controller\EditDocumentController` has been marked as deprecated.


Impact
======

Using this method will trigger PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations with extensions using :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick()` are affected.


Migration
=========

Migrate the method to use the :php:`\TYPO3\CMS\Backend\Routing\UriBuilder` API and attach the parameters manually.

Example:

.. code-block:: php

   // Previous
   $old = BackendUtility::editOnClick($params);

   // Migrated
   $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

   // Variant 1
   $params = '&edit[pages][' . $pid . ']=new&returnNewPageId=1';
   $migrated = $uriBuilder->buildUriFromRoute('record_edit') . $params
       . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));

   // Variant 2
   $params = [
       'edit' => [
           'pages' => [
               $pid => 'new',
           ],
        ],
        'returnNewPageId' => 1,
        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
   ];
   $migrated = (string)$uriBuilder->buildUriFromRoute('record_edit', params);

.. index:: Backend, PHP-API, FullyScanned, ext:backend
