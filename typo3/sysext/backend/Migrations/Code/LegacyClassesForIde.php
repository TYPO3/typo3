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

namespace {
    die('Access denied');
}

namespace TYPO3\CMS\Backend\Attribute {
    class Controller extends \TYPO3\CMS\Backend\Attribute\AsController {}
}

namespace TYPO3\CMS\Backend\Form\Element {
    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class InputLinkElement extends \TYPO3\CMS\Backend\Form\Element\LinkElement {}
}

namespace TYPO3\CMS\Backend\Form\Element {
    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class InputDateTimeElement extends \TYPO3\CMS\Backend\Form\Element\DatetimeElement {}
}

namespace TYPO3\CMS\Backend\Form\Element {
    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class InputColorPickerElement extends \TYPO3\CMS\Backend\Form\Element\ColorElement {}
}

namespace TYPO3\CMS\Backend\Provider {
    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class PageTsBackendLayoutDataProvider extends \TYPO3\CMS\Backend\View\BackendLayout\PageTsBackendLayoutDataProvider {}
}

namespace TYPO3\CMS\Recordlist\Browser {
    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class AbstractElementBrowser extends \TYPO3\CMS\Backend\ElementBrowser\AbstractElementBrowser {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class DatabaseBrowser extends \TYPO3\CMS\Backend\ElementBrowser\DatabaseBrowser {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    interface ElementBrowserInterface extends \TYPO3\CMS\Backend\ElementBrowser\ElementBrowserInterface {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class ElementBrowserRegistry extends \TYPO3\CMS\Backend\ElementBrowser\ElementBrowserRegistry {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class FileBrowser extends \TYPO3\CMS\Filelist\ElementBrowser\FileBrowser {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class FolderBrowser extends \TYPO3\CMS\Filelist\ElementBrowser\FolderBrowser {}
}

namespace TYPO3\CMS\Recordlist\Controller {
    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    abstract class AbstractLinkBrowserController extends \TYPO3\CMS\Backend\Controller\AbstractLinkBrowserController {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class AccessDeniedException extends \TYPO3\CMS\Backend\Exception\AccessDeniedException {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class ClearPageCacheController extends \TYPO3\CMS\Backend\Controller\ClearPageCacheController {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class ElementBrowserController extends \TYPO3\CMS\Backend\Controller\ElementBrowserController {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class RecordListController extends \TYPO3\CMS\Backend\Controller\RecordListController {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class RecordDownloadController extends \TYPO3\CMS\Backend\Controller\RecordListDownloadController {}
}

namespace TYPO3\CMS\Recordlist\LinkHandler {
    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class AbstractLinkHandler extends \TYPO3\CMS\Backend\LinkHandler\AbstractLinkHandler {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class FileLinkHandler extends \TYPO3\CMS\Filelist\LinkHandler\FileLinkHandler {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class FolderLinkHandler extends \TYPO3\CMS\Filelist\LinkHandler\FolderLinkHandler {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    interface LinkHandlerInterface extends \TYPO3\CMS\Backend\LinkHandler\LinkHandlerInterface {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class MailLinkHandler extends \TYPO3\CMS\Backend\LinkHandler\MailLinkHandler {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class PageLinkHandler extends \TYPO3\CMS\Backend\LinkHandler\PageLinkHandler {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class TelephoneLinkHandler extends \TYPO3\CMS\Backend\LinkHandler\TelephoneLinkHandler {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class UrlLinkHandler extends \TYPO3\CMS\Backend\LinkHandler\UrlLinkHandler {}
}

namespace TYPO3\CMS\Recordlist\RecordList {
    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class DatabaseRecordList extends \TYPO3\CMS\Backend\RecordList\DatabaseRecordList {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class DownloadRecordList extends \TYPO3\CMS\Backend\RecordList\DownloadRecordList {}
}

namespace TYPO3\CMS\Recordlist\Tree\View {
    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    interface LinkParameterProviderInterface extends \TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface {}
}

namespace TYPO3\CMS\Recordlist\View {
    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class RecordSearchBoxComponent extends \TYPO3\CMS\Backend\View\RecordSearchBoxComponent {}

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    class FolderUtilityRenderer extends \TYPO3\CMS\Backend\View\FolderUtilityRenderer {}
}
