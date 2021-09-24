<?php

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

namespace TYPO3\CMS\Backend\Template\Components;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Shows the path to the current record or file / folder.
 */
class MetaInformation
{
    /**
     * The recordArray.
     * Typically this is a page record
     *
     * @var array
     */
    protected $recordArray = [];

    protected ?ResourceInterface $resource = null;

    public function setResource(ResourceInterface $resource): void
    {
        $this->resource = $resource;
    }

    /**
     * Set the RecordArray
     *
     * @param array $recordArray RecordArray
     */
    public function setRecordArray(array $recordArray)
    {
        $this->recordArray = $recordArray;
    }

    /**
     * Generate the page path for docHeader
     *
     * @return string The page path
     */
    public function getPath()
    {
        $pageRecord = $this->recordArray;
        $title = '';
        if ($this->resource) {
            try {
                $title = $this->resource->getStorage()->getName();
                $title .= $this->resource->getParentFolder()->getReadablePath();
            } catch (ResourceDoesNotExistException|InsufficientFolderAccessPermissionsException $e) {
            }
        } elseif (is_array($pageRecord) && !empty($pageRecord['uid'])) {
            // Is this a real page
            $title = substr($pageRecord['_thePathFull'] ?? '', 0, -1);
            // Remove current page title
            $pos = strrpos($title, $pageRecord['title']);
            if ($pos !== false) {
                $title = substr($title, 0, $pos);
            }
        }
        // Setting the path of the page
        // crop the title to title limit (or 50, if not defined)
        $beUser = $this->getBackendUser();
        $cropLength = empty($beUser->uc['titleLen']) ? 50 : (int)$beUser->uc['titleLen'];
        $croppedTitle = GeneralUtility::fixed_lgd_cs($title, -$cropLength);
        if ($croppedTitle !== $title) {
            $pagePath = '<abbr title="' . htmlspecialchars($title) . '">' . htmlspecialchars($croppedTitle) . '</abbr>';
        } else {
            $pagePath = htmlspecialchars($title);
        }
        return $pagePath;
    }

    /**
     * Setting page icon with context menu + uid for docheader
     *
     * @return string Record info
     */
    public function getRecordInformation()
    {
        $recordInformations = $this->getRecordInformations();
        if (!empty($recordInformations)) {
            $recordInformation = $recordInformations['icon'] .
                ' <strong>' . htmlspecialchars($recordInformations['title']) . ($recordInformations['uid'] !== '' ? '&nbsp;[' . $recordInformations['uid'] . ']' : '') . '</strong>' .
                (!empty($recordInformations['additionalInfo']) ? ' ' . htmlspecialchars($recordInformations['additionalInfo']) : '');
        } else {
            $recordInformation = '';
        }
        return $recordInformation;
    }

    /**
     * Setting page icon
     *
     * @return string Record icon
     */
    public function getRecordInformationIcon()
    {
        $recordInformations = $this->getRecordInformations();
        if (!empty($recordInformations)) {
            $recordInformationIcon = $recordInformations['icon'];
        } else {
            $recordInformationIcon = null;
        }
        return $recordInformationIcon;
    }

    /**
     * Setting page title
     *
     * @return string Record title, already htmlspecialchar()'ed
     */
    public function getRecordInformationTitle()
    {
        $recordInformations = $this->getRecordInformations();
        if (!empty($recordInformations)) {
            $title = $recordInformations['title'];
        } else {
            $title = '';
        }

        // crop the title to title limit (or 50, if not defined)
        $beUser = $this->getBackendUser();
        $cropLength = empty($beUser->uc['titleLen']) ? 50 : $beUser->uc['titleLen'];
        return htmlspecialchars(GeneralUtility::fixed_lgd_cs($title, $cropLength));
    }

    /**
     * Setting page uid
     *
     * @return int|null Record uid
     */
    public function getRecordInformationUid()
    {
        $recordInformations = $this->getRecordInformations();
        if (!empty($recordInformations)) {
            $recordInformationUid = $recordInformations['uid'];
        } else {
            $recordInformationUid = null;
        }
        return $recordInformationUid;
    }

    /**
     * Returns record additional information
     *
     * @return string Record additional information
     */
    public function getRecordInformationAdditionalInfo(): string
    {
        $recordInformations = $this->getRecordInformations();
        return $recordInformations['additionalInfo'] ?? '';
    }

    public function isFileOrFolder(): bool
    {
        return $this->resource !== null;
    }

    /**
     * Setting page array
     *
     * @return array Record info
     */
    protected function getRecordInformations()
    {
        $pageRecord = $this->recordArray;
        if (empty($pageRecord) && $this->resource === null) {
            return [];
        }

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $uid = '';
        $title = '';
        $additionalInfo = (!empty($pageRecord['_additional_info'] ?? '') ? $pageRecord['_additional_info'] : '');
        // Add icon with context menu, etc:
        // If the module is about a FAL resource
        if ($this->resource) {
            try {
                $fileMountTitle = $this->resource->getStorage()->getFileMounts()[$this->resource->getIdentifier()]['title'] ?? '';
                $title = $fileMountTitle ?: $this->resource->getName();
                // If this is a folder but not in within file mount boundaries this is the root folder
                if ($this->resource instanceof FolderInterface && !$this->resource->getStorage()->isWithinFileMountBoundaries($this->resource)) {
                    $theIcon = '<span title="' . htmlspecialchars($title) . '">' . $iconFactory->getIconForResource(
                        $this->resource,
                        Icon::SIZE_SMALL,
                        null,
                        ['mount-root' => true]
                    )->render() . '</span>';
                } else {
                    $theIcon = '<span title="' . htmlspecialchars($title) . '">' . $iconFactory->getIconForResource(
                        $this->resource,
                        Icon::SIZE_SMALL
                    )->render() . '</span>';
                }
                $tableName = ($this->resource->getIdentifier() === $this->resource->getStorage()->getRootLevelFolder()->getIdentifier())
                    ? 'sys_filemounts' : 'sys_file';
                if (method_exists($this->resource, 'getCombinedIdentifier')) {
                    $theIcon = BackendUtility::wrapClickMenuOnIcon($theIcon, $tableName, $this->resource->getCombinedIdentifier());
                }
            } catch (ResourceDoesNotExistException|InsufficientFolderAccessPermissionsException $e) {
                $theIcon = '';
            }
        } elseif (is_array($pageRecord) && !empty($pageRecord['uid'])) {
            // If there IS a real page
            $toolTip = BackendUtility::getRecordToolTip($pageRecord, 'pages');
            $theIcon = '<span ' . $toolTip . '>' . $iconFactory->getIconForRecord('pages', $pageRecord, Icon::SIZE_SMALL)->render() . '</span>';
            // Make Icon:
            $theIcon = BackendUtility::wrapClickMenuOnIcon($theIcon, 'pages', $pageRecord['uid']);
            $uid = $pageRecord['uid'];
            $title = BackendUtility::getRecordTitle('pages', $pageRecord);
        } else {
            // On root-level of page tree
            // Make Icon
            $theIcon = '<span title="' .
                htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) .
                '">' .
                $iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render() . '</span>';
            if ($this->getBackendUser()->isAdmin()) {
                $theIcon = BackendUtility::wrapClickMenuOnIcon($theIcon, 'pages');
            }
            $uid = '0';
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        }
        // returns array for icon, title, uid and additional info
        return [
            'uid' => $uid,
            'icon' => $theIcon,
            'title' => $title,
            'additionalInfo' => $additionalInfo,
        ];
    }

    /**
     * Get LanguageService Object
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Get the Backend User Object
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
