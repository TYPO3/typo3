<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

	// special context menu actions for the import/export module
if (t3lib_extMgm::isLoaded('impexp')) {
	$importExportActions = '
		9000 = DIVIDER

		9100 = ITEM
		9100 {
			name = exportT3d
			label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.exportT3d
			spriteIcon = actions-document-export-t3d
			callbackAction = exportT3d
		}

		9200 = ITEM
		9200 {
			name = importT3d
			label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.importT3d
			spriteIcon = actions-document-import-t3d
			callbackAction = importT3d
		}
	';
}

	// context menu user default configuration
$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] .= '
	options.pageTree {
		doktypesToShowInNewPageDragArea = 1,3,4,6,7,199,254
	}

	options.contextMenu {
		table {
			pages_root {
				disableItems =

				items {
					100 = ITEM
					100 {
						name = view
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.viewPage
						spriteIcon = actions-document-view
						displayCondition = canBeViewed != 0
						callbackAction = viewPage
					}

					200 = ITEM
					200 {
						name = new
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.newPage
						spriteIcon = actions-document-new
						displayCondition = canCreateNewPages != 0
						callbackAction = newPageWizard
					}

					300 = DIVIDER

					400 = ITEM
					400 {
						name = history
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.history
						spriteIcon = actions-document-history-open
						displayCondition = canShowHistory != 0
						callbackAction = openHistoryPopUp
					}

					' . $importExportActions . '
				}
			}

			pages {
				disableItems =

				items {
					100 = ITEM
					100 {
						name = view
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.viewPage
						spriteIcon = actions-document-view
						displayCondition = canBeViewed != 0
						callbackAction = viewPage
					}

					200 = DIVIDER

					300 = ITEM
					300 {
						name = disable
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.disablePage
						spriteIcon = actions-edit-hide
						displayCondition = getRecord|hidden = 0 && canBeDisabledAndEnabled != 0
						callbackAction = disablePage
					}

					400 = ITEM
					400 {
						name = enable
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.enablePage
						spriteIcon = actions-edit-unhide
						displayCondition = getRecord|hidden = 1 && canBeDisabledAndEnabled != 0
						callbackAction = enablePage
					}

					500 = ITEM
					500 {
						name = edit
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.editPageProperties
						spriteIcon = actions-document-open
						displayCondition = canBeEdited != 0
						callbackAction = editPageProperties
					}

					600 = ITEM
					600 {
						name = info
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.info
						spriteIcon = actions-document-info
						displayCondition = canShowInfo != 0
						callbackAction = openInfoPopUp
					}

					700 = ITEM
					700 {
						name = history
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
							name = new
							label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.newPage
							spriteIcon = actions-document-new
							displayCondition = canCreateNewPages != 0
							callbackAction = newPageWizard
						}

						200 = DIVIDER

						300 = ITEM
						300 {
							name = cut
							label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.cutPage
							spriteIcon = actions-edit-cut
							displayCondition = isInCutMode = 0 && canBeCut != 0
							callbackAction = enableCutMode
						}

						400 = ITEM
						400 {
							name = cut
							label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.cutPage
							spriteIcon = actions-edit-cut-release
							displayCondition = isInCutMode = 1 && canBeCut != 0
							callbackAction = disableCutMode
						}

						500 = ITEM
						500 {
							name = copy
							label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.copyPage
							spriteIcon = actions-edit-copy
							displayCondition = isInCopyMode = 0 && canBeCopied != 0
							callbackAction = enableCopyMode
						}

						600 = ITEM
						600 {
							name = copy
							label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.copyPage
							spriteIcon = actions-edit-copy-release
							displayCondition = isInCopyMode = 1 && canBeCopied != 0
							callbackAction = disableCopyMode
						}

						700 = ITEM
						700 {
							name = pasteInto
							label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.pasteIntoPage
							spriteIcon = actions-document-paste-after
							displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1 && canBePastedInto != 0
							callbackAction = pasteIntoNode
						}

						800 = ITEM
						800 {
							name = pasteAfter
							label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.pasteAfterPage
							spriteIcon = actions-document-paste-into
							displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1 && canBePastedAfter != 0
							callbackAction = pasteAfterNode
						}

						900 = DIVIDER

						1000 = ITEM
						1000 {
							name = delete
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
							name = mountAsTreeroot
							label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.tempMountPoint
							spriteIcon = actions-system-extension-documentation
							displayCondition = canBeTemporaryMountPoint != 0
							callbackAction = mountAsTreeRoot
						}

						200 = DIVIDER

						300 = ITEM
						300 {
							name = expandBranch
							label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.expandBranch
							displayCondition =
							callbackAction = expandBranch
						}

						400 = ITEM
						400 {
							name = collapseBranch
							label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.collapseBranch
							displayCondition =
							callbackAction = collapseBranch
						}

						' . $importExportActions . '
					}
				}
			}
		}
	}
';

?>