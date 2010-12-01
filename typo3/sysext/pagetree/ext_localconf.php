<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

	// context menu user default configuration
$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] .= '
	options.pageTree {
		doktypesToShowInNewPageDragArea = 1,3,4,6,7,199,254
	}

	options.contextMenu {
		defaults {
		}

		table {
			pages.items {
				100 = ITEM
				100 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.viewPage
					spriteIcon = actions-document-view
					displayCondition = canBeViewed != 0
					callbackAction = viewPage
				}

				200 = DIVIDER

				300 = ITEM
				300 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.disablePage
					spriteIcon = actions-edit-hide
					displayCondition = getRecord|hidden = 0 && canBeDisabledAndEnabled != 0
					callbackAction = disablePage
				}

				400 = ITEM
				400 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.enablePage
					spriteIcon = actions-edit-unhide
					displayCondition = getRecord|hidden = 1 && canBeDisabledAndEnabled != 0
					callbackAction = enablePage
				}

				500 = ITEM
				500 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.editPageProperties
					spriteIcon = actions-document-open
					displayCondition = canBeEdited != 0
					callbackAction = editPageProperties
				}

				600 = ITEM
				600 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.info
					spriteIcon = actions-document-info
					displayCondition = canShowInfo != 0
					callbackAction = openInfoPopUp
				}

				700 = ITEM
				700 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.history
					spriteIcon = actions-document-history-open
					displayCondition = canShowHistory != 0
					callbackAction = openHistoryPopUp
				}

				800 = DIVIDER

				900 = SUBMENU
				900 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.copyPasteActions

					100 = ITEM
					100 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.newPage
						spriteIcon = actions-document-new
						displayCondition = canCreateNewPages != 0
						callbackAction = newPageWizard
					}

					200 = DIVIDER

					300 = ITEM
					300 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.cutPage
						spriteIcon = actions-edit-cut
						displayCondition = isInCutMode = 0 && canBeCut != 0
						callbackAction = enableCutMode
					}

					400 = ITEM
					400 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.cutPage
						spriteIcon = actions-edit-cut-release
						displayCondition = isInCutMode = 1 && canBeCut != 0
						callbackAction = disableCutMode
					}

					500 = ITEM
					500 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.copyPage
						spriteIcon = actions-edit-copy
						displayCondition = isInCopyMode = 0 && canBeCopied != 0
						callbackAction = enableCopyMode
					}

					600 = ITEM
					600 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.copyPage
						spriteIcon = actions-edit-copy-release
						displayCondition = isInCopyMode = 1 && canBeCopied != 0
						callbackAction = disableCopyMode
					}

					700 = ITEM
					700 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.pasteIntoPage
						spriteIcon = actions-document-paste-after
						displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1
						callbackAction = pasteIntoNode
					}

					800 = ITEM
					800 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.pasteAfterPage
						spriteIcon = actions-document-paste-into
						displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1
						callbackAction = pasteAfterNode
					}

					900 = DIVIDER

					1000 = ITEM
					1000 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.deletePage
						spriteIcon = actions-edit-delete
						displayCondition = canBeRemoved != 0
						callbackAction = removeNode
					}
				}

				1000 = SUBMENU
				1000 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.branchActions

					100 = ITEM
					100 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.tempMountPoint
						spriteIcon = actions-system-extension-documentation
						displayCondition = canBeTemporaryMountPoint != 0
						callbackAction = stub
					}
				}
			}
		}

		files {
			items {
				100 = ITEM
				100 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.renameFolder
					spriteIcon = actions-edit-rename
					callbackAction = renameFolder
				}

				200 = ITEM
				200 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.uploadFilesToFolder
					spriteIcon = actions-edit-upload
					callbackAction = uploadFilesToFolder
				}

				300 = ITEM
				300 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.createFolder
					spriteIcon = actions-edit-add
					callbackAction = createFolder
				}

				400 = ITEM
				400 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.folderInfo
					spriteIcon = actions-document-info
					callbackAction = openInfoPopUp
				}

				500 = DIVIDER

				600 = ITEM
				600 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.copyFolder
					spriteIcon = actions-edit-copy
					callbackAction = copyFolder
				}

				700 = ITEM
				700 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.cutFolder
					spriteIcon = actions-edit-cut
					callbackAction = cutFolder
				}

				800 = ITEM
				800 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.pasteIntoFolder
					spriteIcon = actions-document-paste-after
					callbackAction = pasteIntoFolder
				}

				900 = DIVIDER

				1000 = ITEM
				1000 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.deleteFolder
					spriteIcon = actions-edit-delete
					callbackAction = deleteFolder
				}
			}
		}
	}
';

?>