mod.wizards.newContentElement {
	renderMode = tabs
	wizardItems {
		common.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common
		common.elements {
			header {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/regular_header.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_headerOnly_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_headerOnly_description
				tt_content_defValues {
					CType = header
				}
			}

			text {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/regular_text.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_regularText_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_regularText_description
				tt_content_defValues {
					CType = text
				}
			}

			textpic {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/text_image_right.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_textImage_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_textImage_description
				tt_content_defValues {
					CType = textpic
					imageorient = 17
				}
			}

			image {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/images_only.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_imagesOnly_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_imagesOnly_description
				tt_content_defValues {
					CType = image
				}
			}

			bullets {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/bullet_list.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_bulletList_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_bulletList_description
				tt_content_defValues {
					CType = bullets
				}
			}

			table {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/table.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_table_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_table_description
				tt_content_defValues {
					CType = table
				}
			}
		}

		common.show = header,text,textpic,image,bullets,table

		special.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special
		special.elements {
			uploads {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/filelinks.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_filelinks_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_filelinks_description
				tt_content_defValues {
					CType = uploads
				}
			}

			menu {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/sitemap2.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_menus_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_menus_description
				tt_content_defValues {
					CType = menu
					menu_type = 0
				}
			}

			html {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/html.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_plainHTML_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_plainHTML_description
				tt_content_defValues {
					CType = html
				}
			}

			div {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/div.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_divider_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_divider_description
				tt_content_defValues {
					CType = div
				}
			}

			shortcut {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/shortcut.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_shortcut_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_shortcut_description
				tt_content_defValues {
					CType = shortcut
				}
			}
		}

		special.show = uploads,menu,html,div,shortcut

		# dummy placeholder for forms group
		forms.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms

		plugins.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:plugins
		plugins.elements {
			general {
				icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/user_defined.gif
				title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:plugins_general_title
				description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:plugins_general_description
				tt_content_defValues.CType = list
			}
		}

		plugins.show = *
	}
}