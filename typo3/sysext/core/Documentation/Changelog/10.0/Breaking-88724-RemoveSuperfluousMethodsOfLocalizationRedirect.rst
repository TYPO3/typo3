.. include:: /Includes.rst.txt

=====================================================================
Breaking: #88724 - Remove superfluous methods of localizationRedirect
=====================================================================

See :issue:`88724`

Description
===========

The method :php:`localizationRedirect` in PageLayoutView, DatabaseRecordList and EditDocumentController were almost equal.
The usage has been streamlined and the methods in PageLayoutView and DatabaseRecordList have been removed.


Impact
======

Calling the routes `web_layout` or `web_list` with parameter `justLocalized` will not redirect to the translated record anymore.
Calling :php:`TYPO3\CMS\Backend\View\PageLayoutView->localizationRedirect` or :php:`TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList->localizationRedirect`
will result in a fatal :php:`E_ERROR`.


Migration
=========

Use route `record_edit` instead of `web_layout` or `web_list`. Set as additional parameter `returnUrl` to the url to the certain module.
Use :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->localizationRedirect` instead of
:php:`TYPO3\CMS\Backend\View\PageLayoutView->localizationRedirect` or :php:`TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList`.

.. index:: PHP-API, NotScanned
