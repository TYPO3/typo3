<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

return [
    'TYPO3\\CMS\\Backend\\ElementBrowser\\FileBrowser' => \TYPO3\CMS\Filelist\ElementBrowser\FileBrowser::class,
    'TYPO3\\CMS\\Backend\\ElementBrowser\\FolderBrowser' => \TYPO3\CMS\Filelist\ElementBrowser\FolderBrowser::class,
    'TYPO3\\CMS\\Backend\\Form\\Element\\InputLinkElement' => \TYPO3\CMS\Backend\Form\Element\LinkElement::class,
    'TYPO3\\CMS\\Backend\\Form\\Element\\InputDateTimeElement' => \TYPO3\CMS\Backend\Form\Element\DatetimeElement::class,
    'TYPO3\\CMS\\Backend\\Form\\Element\\InputColorPickerElement' => \TYPO3\CMS\Backend\Form\Element\ColorElement::class,
    'TYPO3\\CMS\\Backend\\Provider\\PageTsBackendLayoutDataProvider' => \TYPO3\CMS\Backend\View\BackendLayout\PageTsBackendLayoutDataProvider::class,
    'TYPO3\\CMS\\Recordlist\\Browser\\AbstractElementBrowser' => \TYPO3\CMS\Backend\ElementBrowser\AbstractElementBrowser::class,
    'TYPO3\\CMS\\Recordlist\\Browser\\DatabaseBrowser' => \TYPO3\CMS\Backend\ElementBrowser\DatabaseBrowser::class,
    'TYPO3\\CMS\\Recordlist\\Browser\\ElementBrowserInterface' => \TYPO3\CMS\Backend\ElementBrowser\ElementBrowserInterface::class,
    'TYPO3\\CMS\\Recordlist\\Browser\\ElementBrowserRegistry' => \TYPO3\CMS\Backend\ElementBrowser\ElementBrowserRegistry::class,
    'TYPO3\\CMS\\Recordlist\\Browser\\FileBrowser' => \TYPO3\CMS\Filelist\ElementBrowser\FileBrowser::class,
    'TYPO3\\CMS\\Recordlist\\Browser\\FolderBrowser' => \TYPO3\CMS\Filelist\ElementBrowser\FolderBrowser::class,
    'TYPO3\\CMS\\Recordlist\\Controller\\AbstractLinkBrowserController' => \TYPO3\CMS\Backend\Controller\AbstractLinkBrowserController::class,
    'TYPO3\\CMS\\Recordlist\\Controller\\AccessDeniedException' => \TYPO3\CMS\Backend\Exception\AccessDeniedException::class,
    'TYPO3\\CMS\\Recordlist\\Controller\\ClearPageCacheController' => \TYPO3\CMS\Backend\Controller\ClearPageCacheController::class,
    'TYPO3\\CMS\\Recordlist\\Controller\\ElementBrowserController' => \TYPO3\CMS\Backend\Controller\ElementBrowserController::class,
    'TYPO3\\CMS\\Recordlist\\Controller\\RecordListController' => \TYPO3\CMS\Backend\Controller\RecordListController::class,
    'TYPO3\\CMS\\Recordlist\\Controller\\RecordDownloadController' => \TYPO3\CMS\Backend\Controller\RecordListDownloadController::class,
    'TYPO3\\CMS\\Recordlist\\Event\\RenderAdditionalContentToRecordListEvent' => \TYPO3\CMS\Backend\Controller\Event\RenderAdditionalContentToRecordListEvent::class,
    'TYPO3\\CMS\\Recordlist\\Event\\ModifyRecordListHeaderColumnsEvent' => \TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListHeaderColumnsEvent::class,
    'TYPO3\\CMS\\Recordlist\\Event\\ModifyRecordListRecordActionsEvent' => \TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent::class,
    'TYPO3\\CMS\\Recordlist\\Event\\ModifyRecordListTableActionsEvent' => \TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListTableActionsEvent::class,
    'TYPO3\\CMS\\Recordlist\\LinkHandler\\AbstractLinkHandler' => \TYPO3\CMS\Backend\LinkHandler\AbstractLinkHandler::class,
    'TYPO3\\CMS\\Recordlist\\LinkHandler\\FileLinkHandler' => \TYPO3\CMS\Filelist\LinkHandler\FileLinkHandler::class,
    'TYPO3\\CMS\\Recordlist\\LinkHandler\\FolderLinkHandler' => \TYPO3\CMS\Filelist\LinkHandler\FolderLinkHandler::class,
    'TYPO3\\CMS\\Recordlist\\LinkHandler\\LinkHandlerInterface' => \TYPO3\CMS\Backend\LinkHandler\LinkHandlerInterface::class,
    'TYPO3\\CMS\\Recordlist\\LinkHandler\\MailLinkHandler' => \TYPO3\CMS\Backend\LinkHandler\MailLinkHandler::class,
    'TYPO3\\CMS\\Recordlist\\LinkHandler\\PageLinkHandler' => \TYPO3\CMS\Backend\LinkHandler\PageLinkHandler::class,
    'TYPO3\\CMS\\Recordlist\\LinkHandler\\RecordLinkHandler' => \TYPO3\CMS\Backend\LinkHandler\RecordLinkHandler::class,
    'TYPO3\\CMS\\Recordlist\\LinkHandler\\TelephoneLinkHandler' => \TYPO3\CMS\Backend\LinkHandler\TelephoneLinkHandler::class,
    'TYPO3\\CMS\\Recordlist\\LinkHandler\\UrlLinkHandler' => \TYPO3\CMS\Backend\LinkHandler\UrlLinkHandler::class,
    'TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList' => \TYPO3\CMS\Backend\RecordList\DatabaseRecordList::class,
    'TYPO3\\CMS\\Recordlist\\RecordList\\DownloadRecordList' => \TYPO3\CMS\Backend\RecordList\DownloadRecordList::class,
    'TYPO3\\CMS\\Recordlist\\Tree\\View\\LinkParameterProviderInterface' => \TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface::class,
    'TYPO3\\CMS\\Recordlist\\View\\RecordSearchBoxComponent' => \TYPO3\CMS\Backend\View\RecordSearchBoxComponent::class,
    'TYPO3\\CMS\\Recordlist\\View\\FolderUtilityRenderer' => \TYPO3\CMS\Backend\View\FolderUtilityRenderer::class,
    'TYPO3\\CMS\\Backend\\Attribute\\Controller' => \TYPO3\CMS\Backend\Attribute\AsController::class,
];
