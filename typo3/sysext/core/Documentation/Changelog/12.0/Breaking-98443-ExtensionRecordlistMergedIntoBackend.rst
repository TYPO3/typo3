.. include:: /Includes.rst.txt

.. _breaking-98443-1664275773:

===========================================================
Breaking: #98443 - Extension recordlist merged into backend
===========================================================

See :issue:`98443`

Description
===========

The TYPO3 Core extension "recordlist" has been integrated into the Core
extension "backend". Extension "recordlist" does not exist anymore, all
existing functionality like the "List module" is available within the "backend"
extension.

Impact
======

When upgrading to TYPO3 Core v12, extension "backend" replaces extension "recordlist"
automatically.

The following classes have been renamed:

* :php:`\TYPO3\CMS\Recordlist\Browser\AbstractElementBrowser` to :php:`\TYPO3\CMS\Backend\ElementBrowser\AbstractElementBrowser`
* :php:`\TYPO3\CMS\Recordlist\Browser\DatabaseBrowser` to :php:`\TYPO3\CMS\Backend\ElementBrowser\DatabaseBrowser`
* :php:`\TYPO3\CMS\Recordlist\Browser\ElementBrowserInterface` to :php:`\TYPO3\CMS\Backend\ElementBrowser\ElementBrowserInterface`
* :php:`\TYPO3\CMS\Recordlist\Browser\ElementBrowserRegistry` to :php:`\TYPO3\CMS\Backend\ElementBrowser\ElementBrowserRegistry`
* :php:`\TYPO3\CMS\Recordlist\Browser\FileBrowser` to :php:`\TYPO3\CMS\Backend\ElementBrowser\FileBrowser`
* :php:`\TYPO3\CMS\Recordlist\Browser\FolderBrowser` to :php:`\TYPO3\CMS\Backend\ElementBrowser\FolderBrowser`
* :php:`\TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController` to :php:`\TYPO3\CMS\Backend\Controller\AbstractLinkBrowserController`
* :php:`\TYPO3\CMS\Recordlist\Controller\AccessDeniedException` to :php:`\TYPO3\CMS\Backend\Exception\AccessDeniedException`
* :php:`\TYPO3\CMS\Recordlist\Controller\ClearPageCacheController` to :php:`\TYPO3\CMS\Backend\Controller\ClearPageCacheController`
* :php:`\TYPO3\CMS\Recordlist\Controller\ElementBrowserController` to :php:`\TYPO3\CMS\Backend\Controller\ElementBrowserController`
* :php:`\TYPO3\CMS\Recordlist\Controller\RecordListController` to :php:`\TYPO3\CMS\Backend\Controller\RecordListController`
* :php:`\TYPO3\CMS\Recordlist\Controller\RecordDownloadController` to :php:`\TYPO3\CMS\Backend\Controller\RecordListDownloadController`
* :php:`\TYPO3\CMS\Recordlist\Event\RenderAdditionalContentToRecordListEvent` to :php:`\TYPO3\CMS\Backend\Controller\Event\RenderAdditionalContentToRecordListEvent`
* :php:`\TYPO3\CMS\Recordlist\Event\ModifyRecordListHeaderColumnsEvent` to :php:`\TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListHeaderColumnsEvent`
* :php:`\TYPO3\CMS\Recordlist\Event\ModifyRecordListRecordActionsEvent` to :php:`\TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent`
* :php:`\TYPO3\CMS\Recordlist\Event\ModifyRecordListTableActionsEvent` to :php:`\TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListTableActionsEvent`
* :php:`\TYPO3\CMS\Recordlist\LinkHandler\AbstractLinkHandler` to :php:`\TYPO3\CMS\Backend\LinkHandler\AbstractLinkHandler`
* :php:`\TYPO3\CMS\Recordlist\LinkHandler\FileLinkHandler` to :php:`\TYPO3\CMS\Backend\LinkHandler\FileLinkHandler`
* :php:`\TYPO3\CMS\Recordlist\LinkHandler\FolderLinkHandler` to :php:`\TYPO3\CMS\Backend\LinkHandler\FolderLinkHandler`
* :php:`\TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface` to :php:`\TYPO3\CMS\Backend\LinkHandler\LinkHandlerInterface`
* :php:`\TYPO3\CMS\Recordlist\LinkHandler\MailLinkHandler` to :php:`\TYPO3\CMS\Backend\LinkHandler\MailLinkHandler`
* :php:`\TYPO3\CMS\Recordlist\LinkHandler\PageLinkHandler` to :php:`\TYPO3\CMS\Backend\LinkHandler\PageLinkHandler`
* :php:`\TYPO3\CMS\Recordlist\LinkHandler\RecordLinkHandler` to :php:`\TYPO3\CMS\Backend\LinkHandler\RecordLinkHandler`
* :php:`\TYPO3\CMS\Recordlist\LinkHandler\TelephoneLinkHandler` to :php:`\TYPO3\CMS\Backend\LinkHandler\TelephoneLinkHandler`
* :php:`\TYPO3\CMS\Recordlist\LinkHandler\UrlLinkHandler` to :php:`\TYPO3\CMS\Backend\LinkHandler\UrlLinkHandler`
* :php:`\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList` to :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList`
* :php:`\TYPO3\CMS\Recordlist\RecordList\DownloadRecordList` to :php:`\TYPO3\CMS\Backend\RecordList\DownloadRecordList`
* :php:`\TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface` to :php:`\TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface`
* :php:`\TYPO3\CMS\Recordlist\View\RecordSearchBoxComponent` to :php:`\TYPO3\CMS\Backend\View\RecordSearchBoxComponent`
* :php:`\TYPO3\CMS\Recordlist\View\FolderUtilityRenderer` to :php:`\TYPO3\CMS\Backend\View\FolderUtilityRenderer`

Affected installations
======================

Extension "recordlist" was a hard dependency of a working TYPO3 instance and always
installed. When upgrading to TYPO3 Core v12, the TYPO3 Package Manager will simply
ignore the extension now.

Extension extending PHP classes or implementing interfaces
of "recordlist" will continue to work, all moved classes and interfaces have been
established as aliases. Extensions should update their dependencies in case they are
extending or implementing specific "recordlist" functionality, the extension scanner
will find possible usages.

Extensions using the LinkHandler API might need to update corresponding
:typoscript:`TCEMAIN.linkHandler.*` configuration.

Migration
=========

The "typo3/cms-recordlist" dependency can be safely removed as Composer dependency:

..  code-block:: shell

    composer rem typo3/cms-recordlist

Extensions using classes of extension "recordlist" should use the new classes instead.
Extensions supporting both TYPO3 v11 and v12 can continue to use the old class names
since they have been established as aliases to the new class names. These aliases will
be removed with TYPO3 Core v13.

Extensions using the :php:`TYPO3\CMS\Recordlist\LinkHandler\RecordLinkHandler`
as :typoscript:`handler` for a custom :typoscript:`linkHandler` should adjust
corresponding TSconfig to use the new class name
:php:`TYPO3\CMS\Backend\LinkHandler\RecordLinkHandler`. Corresponding service
alias will be removed in TYPO3 v13.

.. index:: Backend, PHP-API, FullyScanned, ext:recordlist
