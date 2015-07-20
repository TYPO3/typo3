# *******************************************************
# Define content elements in "New Content Element Wizard"
# *******************************************************

mod.wizards.newContentElement.wizardItems {
	common.elements {
		header {
			icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/regular_header.gif
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_headerOnly_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_headerOnly_description
			tt_content_defValues {
				CType = header
			}
		}
		textmedia {
			icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/text_image_right.gif
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_textMedia_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_textMedia_description
			tt_content_defValues {
				CType = textmedia
				imageorient = 17
			}
		}
		bullets {
			icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/bullet_list.gif
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_bulletList_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_bulletList_description
			tt_content_defValues {
				CType = bullets
			}
		}
		table {
			icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/table.gif
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_table_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common_table_description
			tt_content_defValues {
				CType = table
			}
		}
		uploads {
			icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/filelinks.gif
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
			icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/sitemap2.gif
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_menus_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_menus_description
			tt_content_defValues {
				CType = menu
				menu_type = 0
			}
		}
		html {
			icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/html.gif
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_plainHTML_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_plainHTML_description
			tt_content_defValues {
				CType = html
			}
		}
		div {
			icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/div.gif
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_divider_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_divider_description
			tt_content_defValues {
				CType = div
			}
		}
		shortcut {
			icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/shortcut.gif
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_shortcut_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_shortcut_description
			tt_content_defValues {
				CType = shortcut
			}
		}
	}
	special.show := addToList(menu,html,div,shortcut)
}
