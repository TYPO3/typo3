<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

	// pagetree user default configuration
$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] .= '
	options.pageTree.doktypesToShowInNewPageDragArea = 1,3,4,6,7,199,254
';

	// contextmenu user default configuration
$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] .= '
	options.contextMenu {
		defaults {
		}

		table {
			pages.items {
				100 = ITEM
				100 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.viewPage
					outerIcon = actions-document-view
					icon =
					callbackAction = TYPO3.Components.PageTree.ContextMenuActions.viewPage
				}

				200 = DIVIDER

				300 = ITEM
				300 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.disablePage
					outerIcon = actions-edit-hide
					icon =
					callbackAction = TYPO3.Components.PageTree.ContextMenuActions.disablePage
				}

				400 = ITEM
				400 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.enablePage
					outerIcon = actions-edit-unhide
					icon =
					callbackAction = TYPO3.Components.PageTree.ContextMenuActions.enablePage
				}

				500 = ITEM
				500 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.editPageProperties
					outerIcon = actions-document-open
					icon =
					callbackAction = TYPO3.Components.PageTree.ContextMenuActions.editPageProperties
				}

				600 = ITEM
				600 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.info
					outerIcon = actions-document-info
					icon =
					callbackAction = TYPO3.Components.PageTree.ContextMenuActions.openInfoPopUp
				}

				700 = ITEM
				700 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.history
					outerIcon = apps-pagetree-page-default+status-overlay-timing
					icon =
					callbackAction = TYPO3.Components.PageTree.ContextMenuActions.openHistoryPopUp
				}

				800 = DIVIDER

				900 = SUBMENU
				900 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.copyPasteActions

					100 = ITEM
					100 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.newPage
						outerIcon = actions-document-new
						icon =
						callbackAction = TYPO3.Components.PageTree.ContextMenuActions.newPageWizard
					}

					200 = DIVIDER

					300 = ITEM
					300 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.cutPage
						outerIcon = actions-edit-cut
						icon =
						callbackAction = TYPO3.Components.PageTree.ContextMenuActions.stub
					}

					400 = ITEM
					400 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.copyPage
						outerIcon = actions-edit-copy
						icon =
						callbackAction = TYPO3.Components.PageTree.ContextMenuActions.stub
					}

					500 = ITEM
					500 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.pasteIntoPage
						outerIcon = actions-document-paste-after
						icon =
						callbackAction = TYPO3.Components.PageTree.ContextMenuActions.stub
					}

					600 = ITEM
					600 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.pasteAfterPage
						outerIcon = actions-document-paste-into
						icon =
						callbackAction = TYPO3.Components.PageTree.ContextMenuActions.stub
					}

					700 = DIVIDER

					800 = ITEM
					800 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.deletePage
						outerIcon = actions-edit-delete
						icon =
						callbackAction = TYPO3.Components.PageTree.ContextMenuActions.stub
					}
				}

				1000 = SUBMENU
				1000 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.branchActions

					100 = ITEM
					100 {
						label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.tempMountPoint
						outerIcon = actions-system-extension-documentation
						icon =
						callbackAction = TYPO3.Components.PageTree.ContextMenuActions.stub
					}
				}
			}
		}

		files {
			items {
				100 = ITEM
				100 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.renameFolder
					outerIcon = actions-edit-rename
					icon =
					callbackAction = TYPO3.Widget.ContextMenu.FolderActions.renameFolder
				}

				200 = ITEM
				200 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.uploadFilesToFolder
					outerIcon = actions-edit-upload
					icon =
					callbackAction = TYPO3.Widget.ContextMenu.FolderActions.uploadFilesToFolder
				}

				300 = ITEM
				300 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.createFolder
					outerIcon = actions-edit-add
					icon =
					callbackAction = TYPO3.Components.PageTree.ContextMenuActions.createFolder
				}

				400 = ITEM
				400 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.folderInfo
					outerIcon = actions-document-info
					icon =
					callbackAction = TYPO3.Widget.ContextMenu.FolderActions.openInfoPopUp
				}

				500 = DIVIDER

				600 = ITEM
				600 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.copyFolder
					outerIcon = actions-edit-copy
					icon =
					callbackAction = TYPO3.Widget.ContextMenu.FolderActions.copyFolder
				}

				700 = ITEM
				700 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.cutFolder
					outerIcon = actions-edit-cut
					icon =
					callbackAction = TYPO3.Widget.ContextMenu.FolderActions.cutFolder
				}

				800 = ITEM
				800 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.pasteIntoFolder
					outerIcon = actions-document-paste-after
					icon =
					callbackAction = TYPO3.Components.PageTree.ContextMenuActions.pasteIntoFolder
				}

				900 = DIVIDER

				1000 = ITEM
				1000 {
					label = LLL:EXT:pagetree/locallang_contextmenu.xml:cm.deleteFolder
					outerIcon = actions-edit-delete
					icon =
					callbackAction = TYPO3.Components.PageTree.ContextMenuActions.deleteFolder
				}
			}
		}
	}
';

?>