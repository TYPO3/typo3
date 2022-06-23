.. include:: /Includes.rst.txt
formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Form.formEditor

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Recommended

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2-

         Form:
           formEditor:
             predefinedDefaults:
               renderingOptions:
                 submitButtonLabel: 'formEditor.elements.Form.editor.submitButtonLabel.value'
             editors:
               100:
                 identifier: header
                 templateName: Inspector-FormElementHeaderEditor
               200:
                 identifier: label
                 templateName: Inspector-TextEditor
                 label: formEditor.elements.BaseFormElementMixin.editor.label.label
                 propertyPath: label
               300:
                 identifier: 'submitButtonLabel'
                 templateName: 'Inspector-TextEditor'
                 label: 'formEditor.elements.Form.editor.submitButtonLabel.label'
                 propertyPath: 'renderingOptions.submitButtonLabel'
               900:
                 identifier: finishers
                 templateName: Inspector-FinishersEditor
                 label: formEditor.elements.Form.editor.finishers.label
                 selectOptions:
                   10:
                     value: ''
                     label: formEditor.elements.Form.editor.finishers.EmptyValue.label
                   20:
                     value: EmailToSender
                     label: formEditor.elements.Form.editor.finishers.EmailToSender.label
                   30:
                     value: EmailToReceiver
                     label: formEditor.elements.Form.editor.finishers.EmailToReceiver.label
                   40:
                     value: Redirect
                     label: formEditor.elements.Form.editor.finishers.Redirect.label
                   50:
                     value: DeleteUploads
                     label: formEditor.elements.Form.editor.finishers.DeleteUploads.label
                   60:
                     value: Confirmation
                     label: formEditor.elements.Form.editor.finishers.Confirmation.label
             _isCompositeFormElement: false
             _isTopLevelFormElement: true
             saveSuccessFlashMessageTitle: formEditor.elements.Form.saveSuccessFlashMessageTitle
             saveSuccessFlashMessageMessage: formEditor.elements.Form.saveSuccessFlashMessageMessage
             saveErrorFlashMessageTitle: formEditor.elements.Form.saveErrorFlashMessageTitle
             saveErrorFlashMessageMessage: formEditor.elements.Form.saveErrorFlashMessageMessage
             modalValidationErrorsDialogTitle: formEditor.modals.validationErrors.dialogTitle
             modalValidationErrorsConfirmButton: formEditor.modals.validationErrors.confirmButton
             modalInsertElementsDialogTitle: formEditor.modals.insertElements.dialogTitle
             modalInsertPagesDialogTitle: formEditor.modals.newPages.dialogTitle
             modalCloseDialogMessage: formEditor.modals.close.dialogMessage
             modalCloseDialogTitle: formEditor.modals.close.dialogTitle
             modalCloseConfirmButton: formEditor.modals.close.confirmButton
             modalCloseCancleButton: formEditor.modals.close.cancleButton
             modalRemoveElementDialogTitle: formEditor.modals.removeElement.dialogTitle
             modalRemoveElementDialogMessage: formEditor.modals.removeElement.dialogMessage
             modalRemoveElementConfirmButton: formEditor.modals.removeElement.confirmButton
             modalRemoveElementCancleButton: formEditor.modals.removeElement.cancleButton
             modalRemoveElementLastAvailablePageFlashMessageTitle: formEditor.modals.removeElement.lastAvailablePageFlashMessageTitle
             modalRemoveElementLastAvailablePageFlashMessageMessage: formEditor.modals.removeElement.lastAvailablePageFlashMessageMessage
             inspectorEditorFormElementSelectorNoElements: formEditor.inspector.editor.formelement_selector.no_elements
             paginationTitle: formEditor.pagination.title
             iconIdentifier: content-form
             propertyCollections:
               finishers:
                 10:
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.header.label
                     200:
                       identifier: subject
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.subject.label
                       propertyPath: options.subject
                       enableFormelementSelectionButton: true
                       propertyValidators:
                         10: NotEmpty
                         20: FormElementIdentifierWithinCurlyBracesInclusive
                     350:
                       identifier: recipients
                       templateName: Inspector-PropertyGridEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.label
                       propertyPath: options.recipients
                       propertyValidators:
                         10: NotEmpty
                       fieldExplanationText: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.fieldExplanationText
                       isSortable: true
                       enableAddRow: true
                       enableDeleteRow: true
                       useLabelAsFallbackValue: false
                       gridColumns:
                         -
                           name: value
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.value.title
                         -
                           name: label
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.label.title
                     500:
                       identifier: senderAddress
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.senderAddress.label
                       propertyPath: options.senderAddress
                       enableFormelementSelectionButton: true
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: NaiveEmail
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     600:
                       identifier: senderName
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.senderName.label
                       propertyPath: options.senderName
                       enableFormelementSelectionButton: true
                       propertyValidators:
                         10: FormElementIdentifierWithinCurlyBracesInclusive
                     750:
                       identifier: replyToRecipients
                       templateName: Inspector-PropertyGridEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.replyToRecipients.label
                       propertyPath: options.replyToRecipients
                       isSortable: true
                       enableAddRow: true
                       enableDeleteRow: true
                       useLabelAsFallbackValue: false
                       gridColumns:
                         -
                           name: value
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.value.title
                         -
                           name: label
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.label.title
                     850:
                       identifier: carbonCopyRecipients
                       templateName: Inspector-PropertyGridEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.carbonCopyRecipients.label
                       propertyPath: options.carbonCopyRecipients
                       isSortable: true
                       enableAddRow: true
                       enableDeleteRow: true
                       useLabelAsFallbackValue: false
                       gridColumns:
                         -
                           name: value
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.value.title
                         -
                           name: label
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.label.title
                     950:
                       identifier: blindCarbonCopyRecipients
                       templateName: Inspector-PropertyGridEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.blindCarbonCopyRecipients.label
                       propertyPath: options.blindCarbonCopyRecipients
                       isSortable: true
                       enableAddRow: true
                       enableDeleteRow: true
                       useLabelAsFallbackValue: false
                       gridColumns:
                         -
                           name: value
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.value.title
                         -
                           name: label
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.label.title
                     1050:
                       identifier: addHtmlPart
                       templateName: Inspector-CheckboxEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.addHtmlPart.label
                       propertyPath: options.addHtmlPart
                       fieldExplanationText: formEditor.elements.Form.finisher.EmailToSender.editor.addHtmlPart.fieldExplanationText
                     1100:
                       identifier: attachUploads
                       templateName: Inspector-CheckboxEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.attachUploads.label
                       propertyPath: options.attachUploads
                     1200:
                       identifier: language
                       templateName: Inspector-SingleSelectEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.language.label
                       propertyPath: options.translation.language
                       selectOptions:
                         10:
                           value: default
                           label: formEditor.elements.Form.finisher.EmailToSender.editor.language.1
                     1400:
                       identifier: title
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.title.label
                       propertyPath: options.title
                       fieldExplanationText: formEditor.elements.Form.finisher.EmailToSender.editor.title.fieldExplanationText
                       enableFormelementSelectionButton: true
                       propertyValidators:
                         10: FormElementIdentifierWithinCurlyBracesInclusive
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                   identifier: EmailToSender
                 20:
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.header.label
                     200:
                       identifier: subject
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.subject.label
                       propertyPath: options.subject
                       enableFormelementSelectionButton: true
                       propertyValidators:
                         10: NotEmpty
                         20: FormElementIdentifierWithinCurlyBracesInclusive
                     350:
                       identifier: recipients
                       templateName: Inspector-PropertyGridEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.recipients.label
                       propertyPath: options.recipients
                       propertyValidators:
                         10: NotEmpty
                       fieldExplanationText: formEditor.elements.Form.finisher.EmailToReceiver.editor.recipients.fieldExplanationText
                       isSortable: true
                       enableAddRow: true
                       enableDeleteRow: true
                       useLabelAsFallbackValue: false
                       gridColumns:
                         -
                           name: value
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.value.title
                         -
                           name: label
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.label.title
                     500:
                       identifier: senderAddress
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.senderAddress.label
                       propertyPath: options.senderAddress
                       enableFormelementSelectionButton: true
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: NaiveEmail
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     600:
                       identifier: senderName
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.senderName.label
                       propertyPath: options.senderName
                       enableFormelementSelectionButton: true
                       propertyValidators:
                         10: FormElementIdentifierWithinCurlyBracesInclusive
                     750:
                       identifier: replyToRecipients
                       templateName: Inspector-PropertyGridEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.replyToRecipients.label
                       propertyPath: options.replyToRecipients
                       isSortable: true
                       enableAddRow: true
                       enableDeleteRow: true
                       useLabelAsFallbackValue: false
                       gridColumns:
                         -
                           name: value
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.value.title
                         -
                           name: label
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.label.title
                     850:
                       identifier: carbonCopyRecipients
                       templateName: Inspector-PropertyGridEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.carbonCopyRecipients.label
                       propertyPath: options.carbonCopyRecipients
                       isSortable: true
                       enableAddRow: true
                       enableDeleteRow: true
                       useLabelAsFallbackValue: false
                       gridColumns:
                         -
                           name: value
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.value.title
                         -
                           name: label
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.label.title
                     950:
                       identifier: blindCarbonCopyRecipients
                       templateName: Inspector-PropertyGridEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.blindCarbonCopyRecipients.label
                       propertyPath: options.blindCarbonCopyRecipients
                       isSortable: true
                       enableAddRow: true
                       enableDeleteRow: true
                       useLabelAsFallbackValue: false
                       gridColumns:
                         -
                           name: value
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.value.title
                         -
                           name: label
                           title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.label.title
                     1050:
                       identifier: addHtmlPart
                       templateName: Inspector-CheckboxEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.addHtmlPart.label
                       propertyPath: options.addHtmlPart
                       fieldExplanationText: formEditor.elements.Form.finisher.EmailToReceiver.editor.addHtmlPart.fieldExplanationText
                     1100:
                       identifier: attachUploads
                       templateName: Inspector-CheckboxEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.attachUploads.label
                       propertyPath: options.attachUploads
                     1200:
                       identifier: language
                       templateName: Inspector-SingleSelectEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.language.label
                       propertyPath: options.translation.language
                       selectOptions:
                         10:
                           value: default
                           label: formEditor.elements.Form.finisher.EmailToReceiver.editor.language.1
                     1400:
                       identifier: title
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.title.label
                       propertyPath: options.title
                       fieldExplanationText: formEditor.elements.Form.finisher.EmailToReceiver.editor.title.fieldExplanationText
                       enableFormelementSelectionButton: true
                       propertyValidators:
                         10: FormElementIdentifierWithinCurlyBracesInclusive
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                   identifier: EmailToReceiver
                 30:
                   identifier: Redirect
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.Form.finisher.Redirect.editor.header.label
                     200:
                       identifier: pageUid
                       templateName: Inspector-Typo3WinBrowserEditor
                       label: formEditor.elements.Form.finisher.Redirect.editor.pageUid.label
                       buttonLabel: formEditor.elements.Form.finisher.Redirect.editor.pageUid.buttonLabel
                       browsableType: pages
                       propertyPath: options.pageUid
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: Integer
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     300:
                       identifier: additionalParameters
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.Redirect.editor.additionalParameters.label
                       propertyPath: options.additionalParameters
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                 40:
                   identifier: DeleteUploads
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.Form.finisher.DeleteUploads.editor.header.label
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                 50:
                   identifier: Confirmation
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.Form.finisher.Confirmation.editor.header.label
                     200:
                       identifier: contentElement
                       templateName: Inspector-Typo3WinBrowserEditor
                       label: formEditor.elements.Form.finisher.Confirmation.editor.contentElement.label
                       buttonLabel: formEditor.elements.Form.finisher.Confirmation.editor.contentElement.buttonLabel
                       browsableType: tt_content
                       iconIdentifier: mimetypes-x-content-text
                       propertyPath: options.contentElementUid
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: IntegerOrEmpty
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     300:
                       identifier: message
                       templateName: Inspector-TextareaEditor
                       label: formEditor.elements.Form.finisher.Confirmation.editor.message.label
                       propertyPath: options.message
                       fieldExplanationText: formEditor.elements.Form.finisher.Confirmation.editor.message.fieldExplanationText
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                 60:
                   identifier: Closure
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.Form.finisher.Closure.editor.header.label
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                 70:
                   identifier: FlashMessage
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.Form.finisher.FlashMessage.editor.header.label
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                 80:
                   identifier: SaveToDatabase
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.Form.finisher.SaveToDatabase.editor.header.label
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
