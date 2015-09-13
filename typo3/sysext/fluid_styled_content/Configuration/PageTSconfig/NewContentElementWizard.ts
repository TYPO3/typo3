# *******************************************************
# Define content elements in "New Content Element Wizard"
# *******************************************************

mod.wizards.newContentElement.wizardItems {
	common.elements {
		header {
			iconIdentifier = content-header
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_headerOnly_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_headerOnly_description
			tt_content_defValues {
				CType = header
			}
		}
		textmedia {
			iconIdentifier = content-textpic
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_textMedia_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_textMedia_description
			tt_content_defValues {
				CType = textmedia
				imageorient = 17
			}
		}
		bullets {
			iconIdentifier = content-bullets
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_bulletList_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_bulletList_description
			tt_content_defValues {
				CType = bullets
			}
		}
		table {
			iconIdentifier = content-table
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_table_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_table_description
			tt_content_defValues {
				CType = table
			}
		}
		uploads {
			iconIdentifier = content-special-uploads
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_filelinks_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_filelinks_description
			tt_content_defValues {
				CType = uploads
			}
		}
	}
	common.show := addToList(header,textmedia,bullets,table,uploads)
	common.show := removeFromList(text,textpic)

	special.elements {
		menu {
			iconIdentifier = content-special-menu
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_menus_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_menus_description
			tt_content_defValues {
				CType = menu
				menu_type = 0
			}
		}
		html {
			iconIdentifier = content-special-html
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_plainHTML_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_plainHTML_description
			tt_content_defValues {
				CType = html
			}
		}
		div {
			iconIdentifier = content-special-div
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_divider_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_divider_description
			tt_content_defValues {
				CType = div
			}
		}
		shortcut {
			iconIdentifier = content-special-shortcut
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_shortcut_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_shortcut_description
			tt_content_defValues {
				CType = shortcut
			}
		}
	}
	special.show := addToList(menu,html,div,shortcut)
}
