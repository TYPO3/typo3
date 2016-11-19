mod.wizards {
    newContentElement.wizardItems {
        forms {
            show :=addToList(formframework)
            elements {
                formframework {
                    iconIdentifier = content-elements-mailform
                    title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:forms_mail_title
                    description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:forms_mail_description
                    tt_content_defValues {
                        CType = form_formframework
                    }
                }
            }
        }
    }
}