mod.wizards.newContentElement.wizardItems.forms {
    show :=addToList(formframework)
    elements {
        formframework {
            iconIdentifier = content-form
            title = LLL:EXT:form/Resources/Private/Language/locallang.xlf:form_new_wizard_title
            description = LLL:EXT:form/Resources/Private/Language/locallang:form_new_wizard_description
            tt_content_defValues {
                CType = form_formframework
            }
        }
    }
}
