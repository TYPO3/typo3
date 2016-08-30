<?php
namespace TYPO3\CMS\Impexp;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Adding Import/Export clickmenu item
 */
class Clickmenu
{
    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Processing of clickmenu items
     *
     * @param \TYPO3\CMS\Backend\ClickMenu\ClickMenu $backRef parent
     * @param array $menuItems Menu items array to modify
     * @param string $table Table name
     * @param int $uid Uid of the record
     * @return array Menu item array, returned after modification
     * @todo Skinning for icons...
     */
    public function main(&$backRef, $menuItems, $table, $uid)
    {
        $localItems = [];
        // Show import/export on second level menu OR root level.
        if ($backRef->cmLevel && GeneralUtility::_GP('subname') == 'moreoptions' || $table === 'pages' && $uid == 0) {
            $LL = $this->includeLL();
            $urlParameters = [
                'tx_impexp' => [
                    'action' => 'export'
                ],
                'id' => ($table == 'pages' ? $uid : $backRef->rec['pid'])
            ];
            if ($table == 'pages') {
                $urlParameters['tx_impexp']['pagetree']['id'] = $uid;
                $urlParameters['tx_impexp']['pagetree']['levels'] = 0;
                $urlParameters['tx_impexp']['pagetree']['tables'][] = '_ALL';
            } else {
                $urlParameters['tx_impexp']['record'][] = $table . ':' . $uid;
                $urlParameters['tx_impexp']['external_ref']['tables'][] = '_ALL';
            }
            $url = BackendUtility::getModuleUrl('xMOD_tximpexp', $urlParameters);
            $localItems[] = $backRef->linkItem(
                $this->getLanguageService()->makeEntities($this->getLanguageService()->getLLL('export', $LL)),
                $backRef->excludeIcon($this->iconFactory->getIcon('actions-document-export-t3d', Icon::SIZE_SMALL)),
                $backRef->urlRefForCM($url),
                1
            );
            if ($table === 'pages') {
                $backendUser = $this->getBackendUser();
                $isEnabledForNonAdmin = $backendUser->getTSConfig('options.impexp.enableImportForNonAdminUser');
                if ($backendUser->isAdmin() || !empty($isEnabledForNonAdmin['value'])) {
                    $urlParameters = [
                        'id' => $uid,
                        'table' => $table,
                        'tx_impexp' => [
                            'action' => 'import'
                        ],
                    ];
                    $url = BackendUtility::getModuleUrl('xMOD_tximpexp', $urlParameters);
                    $localItems[] = $backRef->linkItem(
                        $this->getLanguageService()->makeEntities($this->getLanguageService()->getLLL('import', $LL)),
                        $backRef->excludeIcon($this->iconFactory->getIcon('actions-document-import-t3d', Icon::SIZE_SMALL)),
                        $backRef->urlRefForCM($url),
                        1
                    );
                }
            }
        }
        return array_merge($menuItems, $localItems);
    }

    /**
     * Include local lang file and return $LOCAL_LANG array loaded.
     *
     * @return array Local lang array
     */
    public function includeLL()
    {
        return $this->getLanguageService()->includeLLFile('EXT:impexp/Resources/Private/Language/locallang.xlf', false);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
