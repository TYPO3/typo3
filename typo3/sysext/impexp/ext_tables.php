<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = [
        'name' => \TYPO3\CMS\Impexp\Clickmenu::class
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']['impexp']['TYPO3\\CMS\\Impexp\\Task\\ImportExportTask'] = [
        'title' => 'LLL:EXT:impexp/Resources/Private/Language/locallang_csh.xlf:.alttitle',
        'description' => 'LLL:EXT:impexp/Resources/Private/Language/locallang_csh.xlf:.description',
        'icon' => 'EXT:impexp/Resources/Public/Images/export.gif'
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('xMOD_tx_impexp', 'EXT:impexp/Resources/Private/Language/locallang_csh.xlf');
    // CSH labels for TYPO3 4.5 and greater.  These labels override the ones set above, while still falling back to the original labels if no translation is available.
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:impexp/Resources/Private/Language/locallang_csh.xml'][] = 'EXT:impexp/Resources/Private/Language/locallang_csh_45.xlf';
    // Special context menu actions for the import/export module
    $importExportActions = '
		9000 = DIVIDER

		9100 = ITEM
		9100 {
			name = exportT3d
			label = LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:export
			iconName = actions-document-export-t3d
			callbackAction = exportT3d
		}

		9200 = ITEM
		9200 {
			name = importT3d
			label = LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:import
			iconName = actions-document-import-t3d
			callbackAction = importT3d
		}
	';
    // Context menu user default configuration
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
		options.contextMenu.table {
			virtual_root.items {
				' . $importExportActions . '
			}

			pages_root.items {
				' . $importExportActions . '
			}

			pages.items.1000 {
				' . $importExportActions . '
			}
		}
	');
    // Hook into page tree context menu to remove "import" items again if user is not admin or module
    // is not enabled for this user / group
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['backend']['contextMenu']['disableItems'][]
        = \TYPO3\CMS\Impexp\Hook\ContextMenuDisableItemsHook::class . '->disableImportForNonAdmin';
}
