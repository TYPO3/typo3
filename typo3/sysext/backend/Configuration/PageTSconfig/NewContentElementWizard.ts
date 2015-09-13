# dummy placeholders for item groups
mod.wizards.newContentElement.wizardItems {
	common.header = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:common
	special.header = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special
	forms.header = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:forms
	plugins.header = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:plugins
	plugins.elements {
		general {
			iconIdentifier = content-plugin
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:plugins_general_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:plugins_general_description
			tt_content_defValues.CType = list
		}
	}
	plugins.show = *
}
