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
                     300:
                       identifier: recipientAddress
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.recipientAddress.label
                       propertyPath: options.recipientAddress
                       enableFormelementSelectionButton: true
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: NaiveEmail
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     400:
                       identifier: recipientName
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.recipientName.label
                       propertyPath: options.recipientName
                       enableFormelementSelectionButton: true
                       propertyValidators:
                         10: FormElementIdentifierWithinCurlyBracesInclusive
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
                     700:
                       identifier: replyToAddress
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.replyToAddress.label
                       propertyPath: options.replyToAddress
                       enableFormelementSelectionButton: true
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: NaiveEmailOrEmpty
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     800:
                       identifier: carbonCopyAddress
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.carbonCopyAddress.label
                       propertyPath: options.carbonCopyAddress
                       enableFormelementSelectionButton: true
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: NaiveEmailOrEmpty
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     900:
                       identifier: blindCarbonCopyAddress
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.blindCarbonCopyAddress.label
                       propertyPath: options.blindCarbonCopyAddress
                       enableFormelementSelectionButton: true
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: NaiveEmailOrEmpty
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     1000:
                       identifier: format
                       templateName: Inspector-SingleSelectEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.format.label
                       propertyPath: options.format
                       selectOptions:
                         10:
                           value: plaintext
                           label: formEditor.elements.Form.finisher.EmailToSender.editor.format.1
                         20:
                           value: html
                           label: formEditor.elements.Form.finisher.EmailToSender.editor.format.2
                     1100:
                       identifier: attachUploads
                       templateName: Inspector-CheckboxEditor
                       label: formEditor.elements.Form.finisher.EmailToSender.editor.attachUploads.label
                       propertyPath: options.attachUploads
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
                     300:
                       identifier: recipientAddress
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.recipientAddress.label
                       propertyPath: options.recipientAddress
                       enableFormelementSelectionButton: true
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: NaiveEmail
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     400:
                       identifier: recipientName
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.recipientName.label
                       propertyPath: options.recipientName
                       enableFormelementSelectionButton: true
                       propertyValidators:
                         10: FormElementIdentifierWithinCurlyBracesInclusive
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
                     700:
                       identifier: replyToAddress
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.replyToAddress.label
                       propertyPath: options.replyToAddress
                       enableFormelementSelectionButton: true
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: NaiveEmailOrEmpty
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     800:
                       identifier: carbonCopyAddress
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.carbonCopyAddress.label
                       propertyPath: options.carbonCopyAddress
                       enableFormelementSelectionButton: true
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: NaiveEmailOrEmpty
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     900:
                       identifier: blindCarbonCopyAddress
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.blindCarbonCopyAddress.label
                       propertyPath: options.blindCarbonCopyAddress
                       enableFormelementSelectionButton: true
                       propertyValidatorsMode: OR
                       propertyValidators:
                         10: NaiveEmailOrEmpty
                         20: FormElementIdentifierWithinCurlyBracesExclusive
                     1000:
                       identifier: format
                       templateName: Inspector-SingleSelectEditor
                       label: formEditor.elements.Form.finisher.EmailToReceiver.editor.format.label
                       propertyPath: options.format
                       selectOptions:
                         10:
                           value: plaintext
                           label: formEditor.elements.Form.finisher.EmailToSender.editor.format.1
                         20:
                           value: html
                           label: formEditor.elements.Form.finisher.EmailToSender.editor.format.2
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
