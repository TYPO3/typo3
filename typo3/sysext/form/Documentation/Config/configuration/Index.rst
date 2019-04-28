.. include:: ../../Includes.txt


.. _configurationreference-fullconfiguration:

==========================
Full default configuration
==========================


.. code-block:: yaml

    persistenceManager:
      allowedFileMounts:
        10: '1:/form_definitions/'
      allowSaveToExtensionPaths: false
      allowDeleteFromExtensionPaths: false
    prototypes:
      standard:
        formElementsDefinition:
          Form:
            formEditor:
              predefinedDefaults:
                renderingOptions:
                  submitButtonLabel: formEditor.elements.Form.editor.submitButtonLabel.value
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
                  identifier: submitButtonLabel
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.Form.editor.submitButtonLabel.label
                  propertyPath: renderingOptions.submitButtonLabel
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
            rendererClassName: TYPO3\CMS\Form\Domain\Renderer\FluidFormRenderer
            renderingOptions:
              translation:
                translationFile: 'EXT:form/Resources/Private/Language/locallang.xlf'
              templateRootPaths:
                10: 'EXT:form/Resources/Private/Frontend/Templates/'
              partialRootPaths:
                10: 'EXT:form/Resources/Private/Frontend/Partials/'
              layoutRootPaths:
                10: 'EXT:form/Resources/Private/Frontend/Layouts/'
              addQueryString: false
              argumentsToBeExcludedFromQueryString: {  }
              additionalParams: {  }
              controllerAction: perform
              httpMethod: post
              httpEnctype: multipart/form-data
              _isCompositeFormElement: false
              _isTopLevelFormElement: true
              honeypot:
                enable: true
                formElementToUse: Honeypot
              submitButtonLabel: Submit
              skipUnknownElements: true
          Page:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.Page.editor.label.label
                  propertyPath: label
                300:
                  identifier: previousButtonLabel
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.Page.editor.previousButtonLabel.label
                  propertyPath: renderingOptions.previousButtonLabel
                400:
                  identifier: nextButtonLabel
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.Page.editor.nextButtonLabel.label
                  propertyPath: renderingOptions.nextButtonLabel
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                renderingOptions:
                  previousButtonLabel: formEditor.elements.Page.editor.previousButtonLabel.value
                  nextButtonLabel: formEditor.elements.Page.editor.nextButtonLabel.value
              label: formEditor.elements.Page.label
              group: page
              groupSorting: 100
              _isTopLevelFormElement: true
              _isCompositeFormElement: true
              iconIdentifier: t3-form-icon-page
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\Page
            renderingOptions:
              _isTopLevelFormElement: true
              _isCompositeFormElement: true
              nextButtonLabel: 'next Page'
              previousButtonLabel: 'previous Page'
          SummaryPage:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.SummaryPage.editor.label.label
                  propertyPath: label
                300:
                  identifier: previousButtonLabel
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.SummaryPage.editor.previousButtonLabel.label
                  propertyPath: renderingOptions.previousButtonLabel
                400:
                  identifier: nextButtonLabel
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.SummaryPage.editor.nextButtonLabel.label
                  propertyPath: renderingOptions.nextButtonLabel
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                renderingOptions:
                  previousButtonLabel: formEditor.elements.SummaryPage.editor.previousButtonLabel.value
                  nextButtonLabel: formEditor.elements.SummaryPage.editor.nextButtonLabel.value
              label: formEditor.elements.SummaryPage.label
              group: page
              groupSorting: 200
              _isTopLevelFormElement: true
              _isCompositeFormElement: false
              iconIdentifier: t3-form-icon-summary-page
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\Page
            renderingOptions:
              _isTopLevelFormElement: true
              _isCompositeFormElement: false
              nextButtonLabel: 'next Page'
              previousButtonLabel: 'previous Page'
          Fieldset:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.Fieldset.editor.label.label
                  propertyPath: label
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults: {  }
              label: formEditor.elements.Fieldset.label
              group: container
              groupSorting: 100
              _isCompositeFormElement: true
              iconIdentifier: t3-form-icon-fieldset
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\Section
            properties:
              containerClassAttribute: input
              elementClassAttribute: ''
              elementErrorClassAttribute: error
            renderingOptions:
              _isCompositeFormElement: true
          GridContainer:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.GridContainer.editor.label.label
                  propertyPath: label
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults: {  }
              label: formEditor.elements.GridContainer.label
              _isCompositeFormElement: true
              _isGridContainerFormElement: true
              iconIdentifier: t3-form-icon-gridcontainer
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GridContainer
            properties:
              containerClassAttribute: input
              elementClassAttribute: container
              elementErrorClassAttribute: error
              gridColumnClassAutoConfiguration:
                gridSize: 12
                viewPorts:
                  xs:
                    classPattern: 'col-xs-{@numbersOfColumnsToUse}'
                  sm:
                    classPattern: 'col-sm-{@numbersOfColumnsToUse}'
                  md:
                    classPattern: 'col-md-{@numbersOfColumnsToUse}'
                  lg:
                    classPattern: 'col-lg-{@numbersOfColumnsToUse}'
            renderingOptions:
              _isCompositeFormElement: true
              _isGridContainerFormElement: true
          GridRow:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.GridRow.editor.label.label
                  propertyPath: label
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults: {  }
              label: formEditor.elements.GridRow.label
              group: container
              groupSorting: 300
              _isCompositeFormElement: true
              _isGridRowFormElement: true
              iconIdentifier: t3-form-icon-gridrow
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GridRow
            properties:
              containerClassAttribute: input
              elementClassAttribute: row
              elementErrorClassAttribute: error
              gridColumnClassAutoConfiguration:
                gridSize: 12
                viewPorts:
                  xs:
                    classPattern: 'col-xs-{@numbersOfColumnsToUse}'
                  sm:
                    classPattern: 'col-sm-{@numbersOfColumnsToUse}'
                  md:
                    classPattern: 'col-md-{@numbersOfColumnsToUse}'
                  lg:
                    classPattern: 'col-lg-{@numbersOfColumnsToUse}'
            renderingOptions:
              _isCompositeFormElement: true
              _isGridRowFormElement: true
          Text:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                400:
                  identifier: placeholder
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.placeholder.label
                  propertyPath: properties.fluidAdditionalAttributes.placeholder
                  doNotSetIfPropertyValueIsEmpty: true
                500:
                  identifier: defaultValue
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.defaultValue.label
                  propertyPath: defaultValue
                600:
                  identifier: pattern
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.pattern.label
                  propertyPath: properties.fluidAdditionalAttributes.pattern
                  fieldExplanationText: formEditor.elements.TextMixin.editor.pattern.fieldExplanationText
                  doNotSetIfPropertyValueIsEmpty: true
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.TextMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                    20:
                      value: Alphanumeric
                      label: formEditor.elements.TextMixin.editor.validators.Alphanumeric.label
                    30:
                      value: Text
                      label: formEditor.elements.TextMixin.editor.validators.Text.label
                    40:
                      value: StringLength
                      label: formEditor.elements.TextMixin.editor.validators.StringLength.label
                    50:
                      value: EmailAddress
                      label: formEditor.elements.TextMixin.editor.validators.EmailAddress.label
                    60:
                      value: Integer
                      label: formEditor.elements.TextMixin.editor.validators.Integer.label
                    70:
                      value: Float
                      label: formEditor.elements.TextMixin.editor.validators.Float.label
                    80:
                      value: NumberRange
                      label: formEditor.elements.TextMixin.editor.validators.NumberRange.label
                    90:
                      value: RegularExpression
                      label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                defaultValue: ''
              propertyCollections:
                validators:
                  10:
                    identifier: Alphanumeric
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Alphanumeric.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  20:
                    identifier: Text
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Text.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  30:
                    identifier: StringLength
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.StringLength.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.minlength
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.maxlength
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  40:
                    identifier: EmailAddress
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  50:
                    identifier: Integer
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Integer.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  60:
                    identifier: Float
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Float.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  70:
                    identifier: NumberRange
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.NumberRange.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.min
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.max
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  80:
                    identifier: RegularExpression
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.header.label
                      200:
                        identifier: regex
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.label
                        fieldExplanationText: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.fieldExplanationText
                        propertyPath: options.regularExpression
                        propertyValidators:
                          10: NotEmpty
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
              label: formEditor.elements.Text.label
              group: input
              groupSorting: 100
              iconIdentifier: t3-form-icon-text
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: ''
              elementErrorClassAttribute: error
          Password:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                400:
                  identifier: placeholder
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.placeholder.label
                  propertyPath: properties.fluidAdditionalAttributes.placeholder
                  doNotSetIfPropertyValueIsEmpty: true
                500:
                  identifier: defaultValue
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.defaultValue.label
                  propertyPath: defaultValue
                600:
                  identifier: pattern
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.pattern.label
                  propertyPath: properties.fluidAdditionalAttributes.pattern
                  fieldExplanationText: formEditor.elements.TextMixin.editor.pattern.fieldExplanationText
                  doNotSetIfPropertyValueIsEmpty: true
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.TextMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                    20:
                      value: Alphanumeric
                      label: formEditor.elements.TextMixin.editor.validators.Alphanumeric.label
                    30:
                      value: Text
                      label: formEditor.elements.TextMixin.editor.validators.Text.label
                    40:
                      value: StringLength
                      label: formEditor.elements.TextMixin.editor.validators.StringLength.label
                    50:
                      value: EmailAddress
                      label: formEditor.elements.TextMixin.editor.validators.EmailAddress.label
                    60:
                      value: Integer
                      label: formEditor.elements.TextMixin.editor.validators.Integer.label
                    70:
                      value: Float
                      label: formEditor.elements.TextMixin.editor.validators.Float.label
                    80:
                      value: NumberRange
                      label: formEditor.elements.TextMixin.editor.validators.NumberRange.label
                    90:
                      value: RegularExpression
                      label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                defaultValue: ''
              propertyCollections:
                validators:
                  10:
                    identifier: Alphanumeric
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Alphanumeric.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  20:
                    identifier: Text
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Text.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  30:
                    identifier: StringLength
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.StringLength.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.minlength
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.maxlength
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  40:
                    identifier: EmailAddress
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  50:
                    identifier: Integer
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Integer.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  60:
                    identifier: Float
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Float.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  70:
                    identifier: NumberRange
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.NumberRange.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.min
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.max
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  80:
                    identifier: RegularExpression
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.header.label
                      200:
                        identifier: regex
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.label
                        fieldExplanationText: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.fieldExplanationText
                        propertyPath: options.regularExpression
                        propertyValidators:
                          10: NotEmpty
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
              label: formEditor.elements.Password.label
              group: input
              groupSorting: 300
              iconIdentifier: t3-form-icon-password
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: ''
              elementErrorClassAttribute: error
          AdvancedPassword:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                300:
                  identifier: confirmationLabel
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.AdvancedPassword.editor.confirmationLabel.label
                  propertyPath: properties.confirmationLabel
                400:
                  identifier: placeholder
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.placeholder.label
                  propertyPath: properties.fluidAdditionalAttributes.placeholder
                  doNotSetIfPropertyValueIsEmpty: true
                600:
                  identifier: pattern
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.pattern.label
                  propertyPath: properties.fluidAdditionalAttributes.pattern
                  fieldExplanationText: formEditor.elements.TextMixin.editor.pattern.fieldExplanationText
                  doNotSetIfPropertyValueIsEmpty: true
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.TextMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                    20:
                      value: Alphanumeric
                      label: formEditor.elements.TextMixin.editor.validators.Alphanumeric.label
                    30:
                      value: Text
                      label: formEditor.elements.TextMixin.editor.validators.Text.label
                    40:
                      value: StringLength
                      label: formEditor.elements.TextMixin.editor.validators.StringLength.label
                    50:
                      value: EmailAddress
                      label: formEditor.elements.TextMixin.editor.validators.EmailAddress.label
                    60:
                      value: Integer
                      label: formEditor.elements.TextMixin.editor.validators.Integer.label
                    70:
                      value: Float
                      label: formEditor.elements.TextMixin.editor.validators.Float.label
                    80:
                      value: NumberRange
                      label: formEditor.elements.TextMixin.editor.validators.NumberRange.label
                    90:
                      value: RegularExpression
                      label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                properties:
                  confirmationLabel: formEditor.element.AdvancedPassword.editor.confirmationLabel.predefinedDefaults
              propertyCollections:
                validators:
                  10:
                    identifier: Alphanumeric
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Alphanumeric.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  20:
                    identifier: Text
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Text.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  30:
                    identifier: StringLength
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.StringLength.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.minlength
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.maxlength
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  40:
                    identifier: EmailAddress
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  50:
                    identifier: Integer
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Integer.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  60:
                    identifier: Float
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Float.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  70:
                    identifier: NumberRange
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.NumberRange.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.min
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.max
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  80:
                    identifier: RegularExpression
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.header.label
                      200:
                        identifier: regex
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.label
                        fieldExplanationText: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.fieldExplanationText
                        propertyPath: options.regularExpression
                        propertyValidators:
                          10: NotEmpty
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
              label: formEditor.elements.AdvancedPassword.label
              group: custom
              groupSorting: 500
              iconIdentifier: t3-form-icon-advanced-password
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: input-medium
              elementErrorClassAttribute: error
              confirmationLabel: ''
              confirmationClassAttribute: input-medium
          Textarea:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                400:
                  identifier: placeholder
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.placeholder.label
                  propertyPath: properties.fluidAdditionalAttributes.placeholder
                  doNotSetIfPropertyValueIsEmpty: true
                500:
                  identifier: defaultValue
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.defaultValue.label
                  propertyPath: defaultValue
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.TextMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                    20:
                      value: Alphanumeric
                      label: formEditor.elements.TextMixin.editor.validators.Alphanumeric.label
                    30:
                      value: Text
                      label: formEditor.elements.TextMixin.editor.validators.Text.label
                    40:
                      value: StringLength
                      label: formEditor.elements.TextMixin.editor.validators.StringLength.label
                    60:
                      value: Integer
                      label: formEditor.elements.TextMixin.editor.validators.Integer.label
                    70:
                      value: Float
                      label: formEditor.elements.TextMixin.editor.validators.Float.label
                    80:
                      value: NumberRange
                      label: formEditor.elements.TextMixin.editor.validators.NumberRange.label
                    90:
                      value: RegularExpression
                      label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                defaultValue: ''
              propertyCollections:
                validators:
                  10:
                    identifier: Alphanumeric
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Alphanumeric.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  20:
                    identifier: Text
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Text.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  30:
                    identifier: StringLength
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.StringLength.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.minlength
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.maxlength
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  40:
                    identifier: EmailAddress
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  50:
                    identifier: Integer
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Integer.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  60:
                    identifier: Float
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Float.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  70:
                    identifier: NumberRange
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.NumberRange.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.min
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.max
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  80:
                    identifier: RegularExpression
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.header.label
                      200:
                        identifier: regex
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.label
                        fieldExplanationText: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.fieldExplanationText
                        propertyPath: options.regularExpression
                        propertyValidators:
                          10: NotEmpty
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
              label: formEditor.elements.Textarea.label
              group: input
              groupSorting: 200
              iconIdentifier: t3-form-icon-textarea
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: xxlarge
              elementErrorClassAttribute: error
          Honeypot:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                400:
                  identifier: placeholder
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.placeholder.label
                  propertyPath: properties.fluidAdditionalAttributes.placeholder
                  doNotSetIfPropertyValueIsEmpty: true
                500:
                  identifier: defaultValue
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.defaultValue.label
                  propertyPath: defaultValue
                600:
                  identifier: pattern
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.pattern.label
                  propertyPath: properties.fluidAdditionalAttributes.pattern
                  fieldExplanationText: formEditor.elements.TextMixin.editor.pattern.fieldExplanationText
                  doNotSetIfPropertyValueIsEmpty: true
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.TextMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                    20:
                      value: Alphanumeric
                      label: formEditor.elements.TextMixin.editor.validators.Alphanumeric.label
                    30:
                      value: Text
                      label: formEditor.elements.TextMixin.editor.validators.Text.label
                    40:
                      value: StringLength
                      label: formEditor.elements.TextMixin.editor.validators.StringLength.label
                    50:
                      value: EmailAddress
                      label: formEditor.elements.TextMixin.editor.validators.EmailAddress.label
                    60:
                      value: Integer
                      label: formEditor.elements.TextMixin.editor.validators.Integer.label
                    70:
                      value: Float
                      label: formEditor.elements.TextMixin.editor.validators.Float.label
                    80:
                      value: NumberRange
                      label: formEditor.elements.TextMixin.editor.validators.NumberRange.label
                    90:
                      value: RegularExpression
                      label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                defaultValue: ''
              propertyCollections:
                validators:
                  10:
                    identifier: Alphanumeric
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Alphanumeric.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  20:
                    identifier: Text
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Text.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  30:
                    identifier: StringLength
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.StringLength.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.minlength
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.maxlength
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  40:
                    identifier: EmailAddress
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  50:
                    identifier: Integer
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Integer.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  60:
                    identifier: Float
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Float.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  70:
                    identifier: NumberRange
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.NumberRange.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.min
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.max
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  80:
                    identifier: RegularExpression
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.header.label
                      200:
                        identifier: regex
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.label
                        fieldExplanationText: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.fieldExplanationText
                        propertyPath: options.regularExpression
                        propertyValidators:
                          10: NotEmpty
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: ''
              elementErrorClassAttribute: error
              renderAsHiddenField: false
              styleAttribute: 'position:absolute; margin:0 0 0 -999em;'
          Hidden:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                300:
                  identifier: defaultValue
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.Hidden.editor.defaultValue.label
                  propertyPath: defaultValue
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                defaultValue: ''
              label: formEditor.elements.Hidden.label
              group: custom
              groupSorting: 300
              iconIdentifier: t3-form-icon-hidden
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: ''
              elementErrorClassAttribute: error
          Email:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                400:
                  identifier: placeholder
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.placeholder.label
                  propertyPath: properties.fluidAdditionalAttributes.placeholder
                  doNotSetIfPropertyValueIsEmpty: true
                500:
                  identifier: defaultValue
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.defaultValue.label
                  propertyPath: defaultValue
                  propertyValidators:
                    10: NaiveEmailOrEmpty
                600:
                  identifier: pattern
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.pattern.label
                  propertyPath: properties.fluidAdditionalAttributes.pattern
                  fieldExplanationText: formEditor.elements.TextMixin.editor.pattern.fieldExplanationText
                  doNotSetIfPropertyValueIsEmpty: true
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.TextMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                    50:
                      value: EmailAddress
                      label: formEditor.elements.TextMixin.editor.validators.EmailAddress.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                defaultValue: ''
                validators:
                  -
                    identifier: EmailAddress
              propertyCollections:
                validators:
                  10:
                    identifier: Alphanumeric
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Alphanumeric.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  20:
                    identifier: Text
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Text.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  30:
                    identifier: StringLength
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.StringLength.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.minlength
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.maxlength
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  40:
                    identifier: EmailAddress
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
                  50:
                    identifier: Integer
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Integer.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  60:
                    identifier: Float
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Float.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  70:
                    identifier: NumberRange
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.NumberRange.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.min
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.max
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  80:
                    identifier: RegularExpression
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.header.label
                      200:
                        identifier: regex
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.label
                        fieldExplanationText: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.fieldExplanationText
                        propertyPath: options.regularExpression
                        propertyValidators:
                          10: NotEmpty
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
              label: formEditor.elements.Email.label
              group: html5
              groupSorting: 100
              iconIdentifier: t3-form-icon-email
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: ''
              elementErrorClassAttribute: error
            validators:
              -
                identifier: EmailAddress
          Telephone:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                400:
                  identifier: placeholder
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.placeholder.label
                  propertyPath: properties.fluidAdditionalAttributes.placeholder
                  doNotSetIfPropertyValueIsEmpty: true
                500:
                  identifier: defaultValue
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.defaultValue.label
                  propertyPath: defaultValue
                600:
                  identifier: pattern
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.pattern.label
                  propertyPath: properties.fluidAdditionalAttributes.pattern
                  fieldExplanationText: formEditor.elements.TextMixin.editor.pattern.fieldExplanationText
                  doNotSetIfPropertyValueIsEmpty: true
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.TextMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                    90:
                      value: RegularExpression
                      label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                defaultValue: ''
                properties:
                  fluidAdditionalAttributes:
                    pattern: '.*'
                validators:
                  -
                    identifier: RegularExpression
                    options:
                      regularExpression: '/^.*$/'
              propertyCollections:
                validators:
                  10:
                    identifier: Alphanumeric
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Alphanumeric.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  20:
                    identifier: Text
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Text.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  30:
                    identifier: StringLength
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.StringLength.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.minlength
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.maxlength
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  40:
                    identifier: EmailAddress
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  50:
                    identifier: Integer
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Integer.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  60:
                    identifier: Float
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Float.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  70:
                    identifier: NumberRange
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.NumberRange.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.min
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.max
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  80:
                    identifier: RegularExpression
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.header.label
                      200:
                        identifier: regex
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.label
                        fieldExplanationText: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.fieldExplanationText
                        propertyPath: options.regularExpression
                        propertyValidators:
                          10: NotEmpty
              label: formEditor.elements.Telephone.label
              group: html5
              groupSorting: 200
              iconIdentifier: t3-form-icon-telephone
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: ''
              elementErrorClassAttribute: error
            validators:
              -
                identifier: RegularExpression
                options:
                  regularExpression: '/^.*$/'
          Url:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                400:
                  identifier: placeholder
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.placeholder.label
                  propertyPath: properties.fluidAdditionalAttributes.placeholder
                  doNotSetIfPropertyValueIsEmpty: true
                500:
                  identifier: defaultValue
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.defaultValue.label
                  propertyPath: defaultValue
                600:
                  identifier: pattern
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.pattern.label
                  propertyPath: properties.fluidAdditionalAttributes.pattern
                  fieldExplanationText: formEditor.elements.TextMixin.editor.pattern.fieldExplanationText
                  doNotSetIfPropertyValueIsEmpty: true
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.TextMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                    90:
                      value: RegularExpression
                      label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                defaultValue: ''
                properties:
                  fluidAdditionalAttributes:
                    pattern: '.*'
                validators:
                  -
                    identifier: RegularExpression
                    options:
                      regularExpression: '/^.*$/'
              propertyCollections:
                validators:
                  10:
                    identifier: Alphanumeric
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Alphanumeric.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  20:
                    identifier: Text
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Text.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  30:
                    identifier: StringLength
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.StringLength.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.minlength
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.maxlength
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  40:
                    identifier: EmailAddress
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  50:
                    identifier: Integer
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Integer.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  60:
                    identifier: Float
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Float.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  70:
                    identifier: NumberRange
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.NumberRange.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.min
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.max
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  80:
                    identifier: RegularExpression
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.header.label
                      200:
                        identifier: regex
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.label
                        fieldExplanationText: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.fieldExplanationText
                        propertyPath: options.regularExpression
                        propertyValidators:
                          10: NotEmpty
              label: formEditor.elements.Url.label
              group: html5
              groupSorting: 300
              iconIdentifier: t3-form-icon-url
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: ''
              elementErrorClassAttribute: error
            validators:
              -
                identifier: RegularExpression
                options:
                  regularExpression: '/^.*$/'
          Number:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                400:
                  identifier: placeholder
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.placeholder.label
                  propertyPath: properties.fluidAdditionalAttributes.placeholder
                  doNotSetIfPropertyValueIsEmpty: true
                500:
                  identifier: defaultValue
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.defaultValue.label
                  propertyPath: defaultValue
                  propertyValidators:
                    10: IntegerOrEmpty
                700:
                  identifier: step
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.step.label
                  propertyPath: properties.fluidAdditionalAttributes.step
                  propertyValidators:
                    10: Integer
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.TextMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                    60:
                      value: Number
                      label: formEditor.elements.Number.editor.validators.Number.label
                    80:
                      value: NumberRange
                      label: formEditor.elements.TextMixin.editor.validators.NumberRange.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                defaultValue: ''
                properties:
                  fluidAdditionalAttributes:
                    step: 1
                validators:
                  -
                    identifier: Number
              propertyCollections:
                validators:
                  10:
                    identifier: Alphanumeric
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Alphanumeric.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  20:
                    identifier: Text
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Text.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  30:
                    identifier: StringLength
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.StringLength.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.minlength
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.maxlength
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  40:
                    identifier: EmailAddress
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  50:
                    identifier: Integer
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Integer.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  60:
                    identifier: Number
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Number.editor.header.label
                  70:
                    identifier: NumberRange
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.NumberRange.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.min
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.max
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
                  80:
                    identifier: RegularExpression
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.header.label
                      200:
                        identifier: regex
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.label
                        fieldExplanationText: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.fieldExplanationText
                        propertyPath: options.regularExpression
                        propertyValidators:
                          10: NotEmpty
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
              label: formEditor.elements.Number.label
              group: html5
              groupSorting: 400
              iconIdentifier: t3-form-icon-number
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: ''
              elementErrorClassAttribute: error
            validators:
              -
                identifier: Number
          Checkbox:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults: {  }
              label: formEditor.elements.Checkbox.label
              group: select
              groupSorting: 100
              iconIdentifier: t3-form-icon-checkbox
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: 'input checkbox'
              elementClassAttribute: add-on
              elementErrorClassAttribute: error
              value: 1
          MultiCheckbox:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                300:
                  identifier: options
                  templateName: Inspector-PropertyGridEditor
                  label: formEditor.elements.SelectionMixin.editor.options.label
                  propertyPath: properties.options
                  isSortable: true
                  enableAddRow: true
                  enableDeleteRow: true
                  removeLastAvailableRowFlashMessageTitle: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageTitle
                  removeLastAvailableRowFlashMessageMessage: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageMessage
                  shouldShowPreselectedValueColumn: multiple
                  multiSelection: true
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.MultiSelectionMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.MultiSelectionMixin.editor.validators.EmptyValue.label
                    20:
                      value: Count
                      label: formEditor.elements.MultiSelectionMixin.editor.validators.Count.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                properties:
                  options: {  }
              propertyCollections:
                validators:
                  10:
                    identifier: Count
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.MultiSelectionMixin.validators.Count.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
              label: formEditor.elements.MultiCheckbox.label
              group: select
              groupSorting: 400
              iconIdentifier: t3-form-icon-multi-checkbox
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: 'input checkbox'
              elementClassAttribute: ''
              elementErrorClassAttribute: error
          MultiSelect:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                250:
                  identifier: inactiveOption
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.SelectionMixin.editor.inactiveOption.label
                  propertyPath: properties.prependOptionLabel
                  fieldExplanationText: formEditor.elements.SelectionMixin.editor.inactiveOption.fieldExplanationText
                  doNotSetIfPropertyValueIsEmpty: true
                300:
                  identifier: options
                  templateName: Inspector-PropertyGridEditor
                  label: formEditor.elements.SelectionMixin.editor.options.label
                  propertyPath: properties.options
                  isSortable: true
                  enableAddRow: true
                  enableDeleteRow: true
                  removeLastAvailableRowFlashMessageTitle: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageTitle
                  removeLastAvailableRowFlashMessageMessage: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageMessage
                  shouldShowPreselectedValueColumn: multiple
                  multiSelection: true
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.MultiSelectionMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.MultiSelectionMixin.editor.validators.EmptyValue.label
                    20:
                      value: Count
                      label: formEditor.elements.MultiSelectionMixin.editor.validators.Count.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                properties:
                  options: {  }
              propertyCollections:
                validators:
                  10:
                    identifier: Count
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.MultiSelectionMixin.validators.Count.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
              label: formEditor.elements.MultiSelect.label
              group: select
              groupSorting: 500
              iconIdentifier: t3-form-icon-multi-select
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: xlarge
              elementErrorClassAttribute: error
          RadioButton:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                300:
                  identifier: options
                  templateName: Inspector-PropertyGridEditor
                  label: formEditor.elements.SelectionMixin.editor.options.label
                  propertyPath: properties.options
                  isSortable: true
                  enableAddRow: true
                  enableDeleteRow: true
                  removeLastAvailableRowFlashMessageTitle: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageTitle
                  removeLastAvailableRowFlashMessageMessage: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageMessage
                  shouldShowPreselectedValueColumn: single
                  multiSelection: false
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                properties:
                  options: {  }
              label: formEditor.elements.RadioButton.label
              group: select
              groupSorting: 300
              iconIdentifier: t3-form-icon-radio-button
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: xlarge
              elementErrorClassAttribute: error
          SingleSelect:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                250:
                  identifier: inactiveOption
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.SelectionMixin.editor.inactiveOption.label
                  propertyPath: properties.prependOptionLabel
                  fieldExplanationText: formEditor.elements.SelectionMixin.editor.inactiveOption.fieldExplanationText
                  doNotSetIfPropertyValueIsEmpty: true
                300:
                  identifier: options
                  templateName: Inspector-PropertyGridEditor
                  label: formEditor.elements.SelectionMixin.editor.options.label
                  propertyPath: properties.options
                  isSortable: true
                  enableAddRow: true
                  enableDeleteRow: true
                  removeLastAvailableRowFlashMessageTitle: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageTitle
                  removeLastAvailableRowFlashMessageMessage: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageMessage
                  shouldShowPreselectedValueColumn: single
                  multiSelection: false
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                properties:
                  options: {  }
              label: formEditor.elements.SingleSelect.label
              group: select
              groupSorting: 200
              iconIdentifier: t3-form-icon-single-select
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              containerClassAttribute: input
              elementClassAttribute: ''
              elementErrorClassAttribute: error
          DatePicker:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                300:
                  identifier: dateFormat
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.DatePicker.editor.dateFormat.label
                  propertyPath: properties.dateFormat
                400:
                  identifier: enableDatePicker
                  templateName: Inspector-CheckboxEditor
                  label: formEditor.elements.DatePicker.editor.enableDatePicker.label
                  propertyPath: properties.enableDatePicker
                500:
                  identifier: displayTimeSelector
                  templateName: Inspector-CheckboxEditor
                  label: formEditor.elements.DatePicker.editor.displayTimeSelector.label
                  propertyPath: properties.displayTimeSelector
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.DatePicker.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.DatePicker.editor.validators.EmptyValue.label
                    20:
                      value: DateTime
                      label: formEditor.elements.DatePicker.editor.validators.DateTime.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                properties:
                  dateFormat: Y-m-d
                  enableDatePicker: true
                  displayTimeSelector: false
              label: formEditor.elements.DatePicker.label
              group: custom
              groupSorting: 200
              iconIdentifier: t3-form-icon-date-picker
              propertyCollections:
                validators:
                  10:
                    identifier: DateTime
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.DatePicker.validators.DateTime.editor.header.label
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\DatePicker
            properties:
              containerClassAttribute: input
              elementClassAttribute: 'small form-control'
              elementErrorClassAttribute: error
              timeSelectorClassAttribute: mini
              timeSelectorHourLabel: ''
              timeSelectorMinuteLabel: ''
              dateFormat: Y-m-d
              enableDatePicker: true
              displayTimeSelector: false
          StaticText:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.ReadOnlyFormElement.editor.label.label
                  propertyPath: label
                300:
                  identifier: staticText
                  templateName: Inspector-TextareaEditor
                  label: formEditor.elements.StaticText.editor.staticText.label
                  propertyPath: properties.text
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                properties:
                  text: ''
              label: formEditor.elements.StaticText.label
              group: custom
              groupSorting: 600
              iconIdentifier: t3-form-icon-static-text
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              text: ''
          ContentElement:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                300:
                  identifier: contentElement
                  templateName: Inspector-Typo3WinBrowserEditor
                  label: formEditor.elements.ContentElement.editor.contentElement.label
                  buttonLabel: formEditor.elements.ContentElement.editor.contentElement.buttonLabel
                  browsableType: tt_content
                  propertyPath: properties.contentElementUid
                  propertyValidatorsMode: OR
                  propertyValidators:
                    10: Integer
                    20: FormElementIdentifierWithinCurlyBracesExclusive
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                properties:
                  contentElementUid: ''
              label: formEditor.elements.ContentElement.label
              group: custom
              groupSorting: 700
              iconIdentifier: t3-form-icon-content-element
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
            properties:
              contentElementUid: ''
          FileUpload:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                300:
                  identifier: allowedMimeTypes
                  templateName: Inspector-MultiSelectEditor
                  label: formEditor.elements.FileUpload.editor.allowedMimeTypes.label
                  propertyPath: properties.allowedMimeTypes
                  selectOptions:
                    10:
                      value: application/msword
                      label: formEditor.elements.FileUpload.editor.allowedMimeTypes.doc
                    20:
                      value: application/vnd.openxmlformats-officedocument.wordprocessingml.document
                      label: formEditor.elements.FileUpload.editor.allowedMimeTypes.docx
                    30:
                      value: application/msexcel
                      label: formEditor.elements.FileUpload.editor.allowedMimeTypes.xls
                    40:
                      value: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
                      label: formEditor.elements.FileUpload.editor.allowedMimeTypes.xlsx
                    50:
                      value: application/pdf
                      label: formEditor.elements.FileUpload.editor.allowedMimeTypes.pdf
                    60:
                      value: application/vnd.oasis.opendocument.text
                      label: formEditor.elements.FileUpload.editor.allowedMimeTypes.odt
                    70:
                      value: application/vnd.oasis.opendocument.spreadsheet-template
                      label: formEditor.elements.FileUpload.editor.allowedMimeTypes.ods
                400:
                  identifier: saveToFileMount
                  templateName: Inspector-SingleSelectEditor
                  label: formEditor.elements.FileUploadMixin.editor.saveToFileMount.label
                  propertyPath: properties.saveToFileMount
                  selectOptions:
                    10:
                      value: '1:/user_upload/'
                      label: '1:/user_upload/'
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: 'validators'
                  templateName: 'Inspector-ValidatorsEditor'
                  label: 'formEditor.elements.FileUploadMixin.editor.validators.label'
                  selectOptions:
                    10:
                      value: ''
                      label: 'formEditor.elements.FileUploadMixin.editor.validators.EmptyValue.label'
                    20:
                      value: 'FileSize'
                      label: 'formEditor.elements.FileUploadMixin.editor.validators.FileSize.label'
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              propertyCollections:
                validators:
                  10:
                    identifier: FileSize
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.FileUploadMixin.validators.FileSize.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: FileSize
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: FileSize
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                properties:
                  saveToFileMount: '1:/user_upload/'
                  allowedMimeTypes:
                    - application/pdf
              label: formEditor.elements.FileUpload.label
              group: custom
              groupSorting: 100
              iconIdentifier: t3-form-icon-file-upload
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload
            properties:
              containerClassAttribute: input
              elementClassAttribute: ''
              elementErrorClassAttribute: error
              saveToFileMount: '1:/user_upload/'
              allowedMimeTypes:
                - application/msword
                - application/vnd.openxmlformats-officedocument.wordprocessingml.document
                - application/vnd.oasis.opendocument.text
                - application/pdf
          ImageUpload:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
                300:
                  identifier: allowedMimeTypes
                  templateName: Inspector-MultiSelectEditor
                  label: formEditor.elements.ImageUpload.editor.allowedMimeTypes.label
                  propertyPath: properties.allowedMimeTypes
                  selectOptions:
                    10:
                      value: image/jpeg
                      label: formEditor.elements.ImageUpload.editor.allowedMimeTypes.jpg
                    20:
                      value: image/png
                      label: formEditor.elements.ImageUpload.editor.allowedMimeTypes.png
                    30:
                      value: image/bmp
                      label: formEditor.elements.ImageUpload.editor.allowedMimeTypes.bmp
                400:
                  identifier: saveToFileMount
                  templateName: Inspector-SingleSelectEditor
                  label: formEditor.elements.FileUploadMixin.editor.saveToFileMount.label
                  propertyPath: properties.saveToFileMount
                  selectOptions:
                    10:
                      value: '1:/user_upload/'
                      label: '1:/user_upload/'
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: 'validators'
                  templateName: 'Inspector-ValidatorsEditor'
                  label: 'formEditor.elements.FileUploadMixin.editor.validators.label'
                  selectOptions:
                    10:
                      value: ''
                      label: 'formEditor.elements.FileUploadMixin.editor.validators.EmptyValue.label'
                    20:
                      value: 'FileSize'
                      label: 'formEditor.elements.FileUploadMixin.editor.validators.FileSize.label'
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              propertyCollections:
                validators:
                  10:
                    identifier: FileSize
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.FileUploadMixin.validators.FileSize.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: FileSize
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: FileSize
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                properties:
                  saveToFileMount: '1:/user_upload/'
                  allowedMimeTypes:
                    - image/jpeg
              label: formEditor.elements.ImageUpload.label
              group: custom
              groupSorting: 400
              iconIdentifier: t3-form-icon-image-upload
            implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload
            properties:
              containerClassAttribute: input
              elementClassAttribute: lightbox
              elementErrorClassAttribute: error
              saveToFileMount: '1:/user_upload/'
              allowedMimeTypes:
                - image/jpeg
                - image/png
                - image/bmp
              imageLinkMaxWidth: 500
              imageMaxWidth: 500
              imageMaxHeight: 500
        finishersDefinition:
          Closure:
            implementationClassName: TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher
            formEditor:
              iconIdentifier: t3-form-icon-finisher
              label: formEditor.elements.Form.finisher.Closure.editor.header.label
              predefinedDefaults:
                options:
                  closure: ''
          Confirmation:
            implementationClassName: TYPO3\CMS\Form\Domain\Finishers\ConfirmationFinisher
            options:
              templateName: 'Confirmation'
              templateRootPaths:
                10: 'EXT:form/Resources/Private/Frontend/Templates/Finishers/Confirmation/'
            formEditor:
              iconIdentifier: t3-form-icon-finisher
              label: formEditor.elements.Form.finisher.Confirmation.editor.header.label
              predefinedDefaults:
                options:
                  message: ''
          EmailToSender:
            implementationClassName: TYPO3\CMS\Form\Domain\Finishers\EmailFinisher
            options:
              templatePathAndFilename: 'EXT:form/Resources/Private/Frontend/Templates/Finishers/Email/{@format}.html'
            formEditor:
              iconIdentifier: t3-form-icon-finisher
              label: formEditor.elements.Form.finisher.EmailToSender.editor.header.label
              predefinedDefaults:
                options:
                  subject: ''
                  recipientAddress: ''
                  recipientName: ''
                  senderAddress: ''
                  senderName: ''
                  replyToAddress: ''
                  carbonCopyAddress: ''
                  blindCarbonCopyAddress: ''
                  format: html
                  attachUploads: true
            FormEngine:
              label: tt_content.finishersDefinition.EmailToSender.label
              elements:
                subject:
                  label: tt_content.finishersDefinition.EmailToSender.subject.label
                  config:
                    type: input
                recipientAddress:
                  label: tt_content.finishersDefinition.EmailToSender.recipientAddress.label
                  config:
                    type: input
                    eval: required
                recipientName:
                  label: tt_content.finishersDefinition.EmailToSender.recipientName.label
                  config:
                    type: input
                senderAddress:
                  label: tt_content.finishersDefinition.EmailToSender.senderAddress.label
                  config:
                    type: input
                    eval: required
                senderName:
                  label: tt_content.finishersDefinition.EmailToSender.senderName.label
                  config:
                    type: input
                replyToAddress:
                  label: tt_content.finishersDefinition.EmailToSender.replyToAddress.label
                  config:
                    type: input
                carbonCopyAddress:
                  label: tt_content.finishersDefinition.EmailToSender.carbonCopyAddress.label
                  config:
                    type: input
                blindCarbonCopyAddress:
                  label: tt_content.finishersDefinition.EmailToSender.blindCarbonCopyAddress.label
                  config:
                    type: input
                format:
                  label: tt_content.finishersDefinition.EmailToSender.format.label
                  config:
                    type: select
                    renderType: selectSingle
                    minitems: 1
                    maxitems: 1
                    size: 1
                    items:
                      10:
                        - tt_content.finishersDefinition.EmailToSender.format.1
                        - html
                      20:
                        - tt_content.finishersDefinition.EmailToSender.format.2
                        - plaintext
          EmailToReceiver:
            implementationClassName: TYPO3\CMS\Form\Domain\Finishers\EmailFinisher
            options:
              templatePathAndFilename: 'EXT:form/Resources/Private/Frontend/Templates/Finishers/Email/{@format}.html'
            formEditor:
              iconIdentifier: t3-form-icon-finisher
              label: formEditor.elements.Form.finisher.EmailToReceiver.editor.header.label
              predefinedDefaults:
                options:
                  subject: ''
                  recipientAddress: ''
                  recipientName: ''
                  senderAddress: ''
                  senderName: ''
                  replyToAddress: ''
                  carbonCopyAddress: ''
                  blindCarbonCopyAddress: ''
                  format: html
                  attachUploads: true
                  translation:
                    language: ''
            FormEngine:
              label: tt_content.finishersDefinition.EmailToReceiver.label
              elements:
                subject:
                  label: tt_content.finishersDefinition.EmailToReceiver.subject.label
                  config:
                    type: input
                recipientAddress:
                  label: tt_content.finishersDefinition.EmailToReceiver.recipientAddress.label
                  config:
                    type: input
                    eval: required
                recipientName:
                  label: tt_content.finishersDefinition.EmailToReceiver.recipientName.label
                  config:
                    type: input
                senderAddress:
                  label: tt_content.finishersDefinition.EmailToReceiver.senderAddress.label
                  config:
                    type: input
                    eval: required
                senderName:
                  label: tt_content.finishersDefinition.EmailToReceiver.senderName.label
                  config:
                    type: input
                replyToAddress:
                  label: tt_content.finishersDefinition.EmailToReceiver.replyToAddress.label
                  config:
                    type: input
                carbonCopyAddress:
                  label: tt_content.finishersDefinition.EmailToReceiver.carbonCopyAddress.label
                  config:
                    type: input
                blindCarbonCopyAddress:
                  label: tt_content.finishersDefinition.EmailToReceiver.blindCarbonCopyAddress.label
                  config:
                    type: input
                format:
                  label: tt_content.finishersDefinition.EmailToReceiver.format.label
                  config:
                    type: select
                    renderType: selectSingle
                    minitems: 1
                    maxitems: 1
                    size: 1
                    items:
                      10:
                        - tt_content.finishersDefinition.EmailToSender.format.1
                        - html
                      20:
                        - tt_content.finishersDefinition.EmailToSender.format.2
                        - plaintext
                translation:
                  language:
                    label: tt_content.finishersDefinition.EmailToReceiver.language.label
                    config:
                      type: select
                      renderType: selectSingle
                      minitems: 1
                      maxitems: 1
                      size: 1
                      items:
                        10:
                          - tt_content.finishersDefinition.EmailToReceiver.language.1
                          - default
          DeleteUploads:
            implementationClassName: TYPO3\CMS\Form\Domain\Finishers\DeleteUploadsFinisher
            formEditor:
              iconIdentifier: t3-form-icon-finisher
              label: formEditor.elements.Form.finisher.DeleteUploads.editor.header.label
          FlashMessage:
            implementationClassName: TYPO3\CMS\Form\Domain\Finishers\FlashMessageFinisher
            formEditor:
              iconIdentifier: t3-form-icon-finisher
              label: formEditor.elements.Form.finisher.FlashMessage.editor.header.label
              predefinedDefaults:
                options:
                  messageBody: ''
                  messageTitle: ''
                  messageArguments: ''
                  messageCode: 0
                  severity: 0
          Redirect:
            implementationClassName: TYPO3\CMS\Form\Domain\Finishers\RedirectFinisher
            formEditor:
              iconIdentifier: t3-form-icon-finisher
              label: formEditor.elements.Form.finisher.Redirect.editor.header.label
              predefinedDefaults:
                options:
                  pageUid: ''
                  additionalParameters: ''
            FormEngine:
              label: tt_content.finishersDefinition.Redirect.label
              elements:
                pageUid:
                  label: tt_content.finishersDefinition.Redirect.pageUid.label
                  config:
                    type: group
                    internal_type: db
                    allowed: pages
                    size: 1
                    minitems: 1
                    maxitems: 1
                    fieldWizard:
                      recordsOverview:
                        disabled: 1
                additionalParameters:
                  label: tt_content.finishersDefinition.Redirect.additionalParameters.label
                  config:
                    type: input
          SaveToDatabase:
            implementationClassName: TYPO3\CMS\Form\Domain\Finishers\SaveToDatabaseFinisher
            formEditor:
              iconIdentifier: t3-form-icon-finisher
              label: formEditor.elements.Form.finisher.SaveToDatabase.editor.header.label
              predefinedDefaults:
                options: {  }
        validatorsDefinition:
          NotEmpty:
            implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.FormElement.editor.requiredValidator.label
          DateTime:
            implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\DateTimeValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.DatePicker.validators.DateTime.editor.header.label
          Alphanumeric:
            implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.TextMixin.editor.validators.Alphanumeric.label
          Text:
            implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\TextValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.TextMixin.editor.validators.Text.label
          StringLength:
            implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.TextMixin.editor.validators.StringLength.label
              predefinedDefaults:
                options:
                  minimum: ''
                  maximum: ''
          EmailAddress:
            implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.TextMixin.editor.validators.EmailAddress.label
          Integer:
            implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.TextMixin.editor.validators.Integer.label
          Float:
            implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\FloatValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.TextMixin.editor.validators.Float.label
          Number:
            implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\NumberValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.TextMixin.editor.validators.Number.label
          NumberRange:
            implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\NumberRangeValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.TextMixin.editor.validators.NumberRange.label
              predefinedDefaults:
                options:
                  minimum: ''
                  maximum: ''
          RegularExpression:
            implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label
              predefinedDefaults:
                options:
                  regularExpression: ''
          Count:
            implementationClassName: TYPO3\CMS\Form\Mvc\Validation\CountValidator
            formEditor:
              iconIdentifier: t3-form-icon-validator
              label: formEditor.elements.MultiSelectionMixin.validators.Count.editor.header.label
              predefinedDefaults:
                options:
                  minimum: ''
                  maximum: ''
          FileSize:
            implementationClassName: TYPO3\CMS\Form\Mvc\Validation\FileSizeValidator
            formEditor:
              iconIdentifier: 't3-form-icon-validator'
              label: 'formEditor.elements.FileUploadMixin.validators.FileSize.editor.header.label'
              predefinedDefaults:
                options:
                  minimum: '0B'
                  maximum: '10M'

        formEditor:
          translationFile: 'EXT:form/Resources/Private/Language/Database.xlf'
          dynamicRequireJsModules:
            app: TYPO3/CMS/Form/Backend/FormEditor
            mediator: TYPO3/CMS/Form/Backend/FormEditor/Mediator
            viewModel: TYPO3/CMS/Form/Backend/FormEditor/ViewModel
          addInlineSettings: {  }
          maximumUndoSteps: 10
          stylesheets:
            200: 'EXT:form/Resources/Public/Css/form.css'
          formEditorFluidConfiguration:
            templatePathAndFilename: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/InlineTemplates.html'
            partialRootPaths:
              10: 'EXT:form/Resources/Private/Backend/Partials/FormEditor/'
            layoutRootPaths:
              10: 'EXT:form/Resources/Private/Backend/Layouts/FormEditor/'
          formEditorPartials:
            FormElement-_ElementToolbar: Stage/_ElementToolbar
            FormElement-_UnknownElement: Stage/_UnknownElement
            FormElement-Page: Stage/Page
            FormElement-SummaryPage: Stage/SummaryPage
            FormElement-Fieldset: Stage/Fieldset
            FormElement-GridContainer: Stage/Fieldset
            FormElement-GridRow: Stage/Fieldset
            FormElement-Text: Stage/SimpleTemplate
            FormElement-Password: Stage/SimpleTemplate
            FormElement-AdvancedPassword: Stage/SimpleTemplate
            FormElement-Textarea: Stage/SimpleTemplate
            FormElement-Checkbox: Stage/SimpleTemplate
            FormElement-MultiCheckbox: Stage/SelectTemplate
            FormElement-MultiSelect: Stage/SelectTemplate
            FormElement-RadioButton: Stage/SelectTemplate
            FormElement-SingleSelect: Stage/SelectTemplate
            FormElement-DatePicker: Stage/SimpleTemplate
            FormElement-StaticText: Stage/StaticText
            FormElement-Hidden: Stage/SimpleTemplate
            FormElement-ContentElement: Stage/ContentElement
            FormElement-FileUpload: Stage/FileUploadTemplate
            FormElement-ImageUpload: Stage/FileUploadTemplate
            FormElement-Email: Stage/SimpleTemplate
            FormElement-Telephone: Stage/SimpleTemplate
            FormElement-Url: Stage/SimpleTemplate
            FormElement-Number: Stage/SimpleTemplate
            Modal-InsertElements: Modals/InsertElements
            Modal-InsertPages: Modals/InsertPages
            Modal-ValidationErrors: Modals/ValidationErrors
            Inspector-FormElementHeaderEditor: Inspector/FormElementHeaderEditor
            Inspector-CollectionElementHeaderEditor: Inspector/CollectionElementHeaderEditor
            Inspector-TextEditor: Inspector/TextEditor
            Inspector-PropertyGridEditor: Inspector/PropertyGridEditor
            Inspector-SingleSelectEditor: Inspector/SingleSelectEditor
            Inspector-MultiSelectEditor: Inspector/MultiSelectEditor
            Inspector-GridColumnViewPortConfigurationEditor: Inspector/GridColumnViewPortConfigurationEditor
            Inspector-TextareaEditor: Inspector/TextareaEditor
            Inspector-RemoveElementEditor: Inspector/RemoveElementEditor
            Inspector-FinishersEditor: Inspector/FinishersEditor
            Inspector-ValidatorsEditor: Inspector/ValidatorsEditor
            Inspector-RequiredValidatorEditor: Inspector/RequiredValidatorEditor
            Inspector-CheckboxEditor: Inspector/CheckboxEditor
            Inspector-Typo3WinBrowserEditor: Inspector/Typo3WinBrowserEditor
          formElementPropertyValidatorsDefinition:
            NotEmpty:
              errorMessage: formEditor.formElementPropertyValidatorsDefinition.NotEmpty.label
            Integer:
              errorMessage: formEditor.formElementPropertyValidatorsDefinition.Integer.label
            NaiveEmail:
              errorMessage: formEditor.formElementPropertyValidatorsDefinition.NaiveEmail.label
            NaiveEmailOrEmpty:
              errorMessage: formEditor.formElementPropertyValidatorsDefinition.NaiveEmail.label
            FormElementIdentifierWithinCurlyBracesInclusive:
              errorMessage: formEditor.formElementPropertyValidatorsDefinition.FormElementIdentifierWithinCurlyBraces.label
            FormElementIdentifierWithinCurlyBracesExclusive:
              errorMessage: formEditor.formElementPropertyValidatorsDefinition.FormElementIdentifierWithinCurlyBraces.label
          formElementGroups:
            input:
              label: formEditor.formElementGroups.input.label
            html5:
              label: formEditor.formElementGroups.html5.label
            select:
              label: formEditor.formElementGroups.select.label
            custom:
              label: formEditor.formElementGroups.custom.label
            container:
              label: formEditor.formElementGroups.container.label
            page:
              label: formEditor.formElementGroups.page.label
        formEngine:
          translationFile: 'EXT:form/Resources/Private/Language/Database.xlf'
    mixins:
      translationSettingsMixin:
        translation:
          translationFile: 'EXT:form/Resources/Private/Language/locallang.xlf'
      formElementMixins:
        BaseFormElementMixin:
          formEditor:
            predefinedDefaults: {  }
            editors:
              100:
                identifier: header
                templateName: Inspector-FormElementHeaderEditor
              200:
                identifier: label
                templateName: Inspector-TextEditor
                label: formEditor.elements.BaseFormElementMixin.editor.label.label
                propertyPath: label
        ReadOnlyFormElementMixin:
          formEditor:
            editors:
              100:
                identifier: header
                templateName: Inspector-FormElementHeaderEditor
              200:
                identifier: label
                templateName: Inspector-TextEditor
                label: formEditor.elements.ReadOnlyFormElement.editor.label.label
                propertyPath: label
              9999:
                identifier: removeButton
                templateName: Inspector-RemoveElementEditor
            predefinedDefaults: {  }
          implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
        FormElementMixin:
          formEditor:
            editors:
              100:
                identifier: header
                templateName: Inspector-FormElementHeaderEditor
              200:
                identifier: label
                templateName: Inspector-TextEditor
                label: formEditor.elements.FormElement.editor.label.label
                propertyPath: label
              230:
                identifier: elementDescription
                templateName: Inspector-TextEditor
                label: formEditor.elements.FormElement.editor.elementDescription.label
                propertyPath: properties.elementDescription
              700:
                identifier: gridColumnViewPortConfiguration
                templateName: Inspector-GridColumnViewPortConfigurationEditor
                label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                configurationOptions:
                  viewPorts:
                    10:
                      viewPortIdentifier: xs
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                    20:
                      viewPortIdentifier: sm
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                    30:
                      viewPortIdentifier: md
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                    40:
                      viewPortIdentifier: lg
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                  numbersOfColumnsToUse:
                    label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                    propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                    fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
              800:
                identifier: requiredValidator
                templateName: Inspector-RequiredValidatorEditor
                label: formEditor.elements.FormElement.editor.requiredValidator.label
                validatorIdentifier: NotEmpty
                propertyPath: properties.fluidAdditionalAttributes.required
                propertyValue: required
                configurationOptions:
                  validationErrorMessage:
                    label: formEditor.elements.FormElement.editor.requiredValidator.validationErrorMessage.label
                    propertyPath: properties.validationErrorMessages
                    fieldExplanationText: formEditor.elements.FormElement.editor.requiredValidator.validationErrorMessage.fieldExplanationText
                    errorCodes:
                      10: 1221560910
                      20: 1221560718
                      30: 1347992400
                      40: 1347992453
              9999:
                identifier: removeButton
                templateName: Inspector-RemoveElementEditor
            predefinedDefaults: {  }
          implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
          properties:
            containerClassAttribute: input
            elementClassAttribute: ''
            elementErrorClassAttribute: error
        TextMixin:
          formEditor:
            editors:
              100:
                identifier: header
                templateName: Inspector-FormElementHeaderEditor
              200:
                identifier: label
                templateName: Inspector-TextEditor
                label: formEditor.elements.FormElement.editor.label.label
                propertyPath: label
              400:
                identifier: placeholder
                templateName: Inspector-TextEditor
                label: formEditor.elements.TextMixin.editor.placeholder.label
                propertyPath: properties.fluidAdditionalAttributes.placeholder
                doNotSetIfPropertyValueIsEmpty: true
              500:
                identifier: defaultValue
                templateName: Inspector-TextEditor
                label: formEditor.elements.TextMixin.editor.defaultValue.label
                propertyPath: defaultValue
              600:
                identifier: pattern
                templateName: Inspector-TextEditor
                label: formEditor.elements.TextMixin.editor.pattern.label
                propertyPath: properties.fluidAdditionalAttributes.pattern
                fieldExplanationText: formEditor.elements.TextMixin.editor.pattern.fieldExplanationText
                doNotSetIfPropertyValueIsEmpty: true
              700:
                identifier: gridColumnViewPortConfiguration
                templateName: Inspector-GridColumnViewPortConfigurationEditor
                label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                configurationOptions:
                  viewPorts:
                    10:
                      viewPortIdentifier: xs
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                    20:
                      viewPortIdentifier: sm
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                    30:
                      viewPortIdentifier: md
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                    40:
                      viewPortIdentifier: lg
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                  numbersOfColumnsToUse:
                    label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                    propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                    fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
              800:
                identifier: requiredValidator
                templateName: Inspector-RequiredValidatorEditor
                label: formEditor.elements.FormElement.editor.requiredValidator.label
                validatorIdentifier: NotEmpty
                propertyPath: properties.fluidAdditionalAttributes.required
                propertyValue: required
              900:
                identifier: validators
                templateName: Inspector-ValidatorsEditor
                label: formEditor.elements.TextMixin.editor.validators.label
                selectOptions:
                  10:
                    value: ''
                    label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                  20:
                    value: Alphanumeric
                    label: formEditor.elements.TextMixin.editor.validators.Alphanumeric.label
                  30:
                    value: Text
                    label: formEditor.elements.TextMixin.editor.validators.Text.label
                  40:
                    value: StringLength
                    label: formEditor.elements.TextMixin.editor.validators.StringLength.label
                  50:
                    value: EmailAddress
                    label: formEditor.elements.TextMixin.editor.validators.EmailAddress.label
                  60:
                    value: Integer
                    label: formEditor.elements.TextMixin.editor.validators.Integer.label
                  70:
                    value: Float
                    label: formEditor.elements.TextMixin.editor.validators.Float.label
                  80:
                    value: NumberRange
                    label: formEditor.elements.TextMixin.editor.validators.NumberRange.label
                  90:
                    value: RegularExpression
                    label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label
              9999:
                identifier: removeButton
                templateName: Inspector-RemoveElementEditor
            predefinedDefaults:
              defaultValue: ''
            propertyCollections:
              validators:
                10:
                  identifier: Alphanumeric
                  editors:
                    100:
                      identifier: header
                      templateName: Inspector-CollectionElementHeaderEditor
                      label: formEditor.elements.TextMixin.validators.Alphanumeric.editor.header.label
                    9999:
                      identifier: removeButton
                      templateName: Inspector-RemoveElementEditor
                20:
                  identifier: Text
                  editors:
                    100:
                      identifier: header
                      templateName: Inspector-CollectionElementHeaderEditor
                      label: formEditor.elements.TextMixin.validators.Text.editor.header.label
                    9999:
                      identifier: removeButton
                      templateName: Inspector-RemoveElementEditor
                30:
                  identifier: StringLength
                  editors:
                    100:
                      identifier: header
                      templateName: Inspector-CollectionElementHeaderEditor
                      label: formEditor.elements.TextMixin.validators.StringLength.editor.header.label
                    200:
                      identifier: minimum
                      templateName: Inspector-TextEditor
                      label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                      propertyPath: options.minimum
                      propertyValidators:
                        10: Integer
                      additionalElementPropertyPaths:
                        10: properties.fluidAdditionalAttributes.minlength
                    300:
                      identifier: maximum
                      templateName: Inspector-TextEditor
                      label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                      propertyPath: options.maximum
                      propertyValidators:
                        10: Integer
                      additionalElementPropertyPaths:
                        10: properties.fluidAdditionalAttributes.maxlength
                    9999:
                      identifier: removeButton
                      templateName: Inspector-RemoveElementEditor
                40:
                  identifier: EmailAddress
                  editors:
                    100:
                      identifier: header
                      templateName: Inspector-CollectionElementHeaderEditor
                      label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
                    9999:
                      identifier: removeButton
                      templateName: Inspector-RemoveElementEditor
                50:
                  identifier: Integer
                  editors:
                    100:
                      identifier: header
                      templateName: Inspector-CollectionElementHeaderEditor
                      label: formEditor.elements.TextMixin.validators.Integer.editor.header.label
                    9999:
                      identifier: removeButton
                      templateName: Inspector-RemoveElementEditor
                60:
                  identifier: Float
                  editors:
                    100:
                      identifier: header
                      templateName: Inspector-CollectionElementHeaderEditor
                      label: formEditor.elements.TextMixin.validators.Float.editor.header.label
                    9999:
                      identifier: removeButton
                      templateName: Inspector-RemoveElementEditor
                70:
                  identifier: NumberRange
                  editors:
                    100:
                      identifier: header
                      templateName: Inspector-CollectionElementHeaderEditor
                      label: formEditor.elements.TextMixin.validators.NumberRange.editor.header.label
                    200:
                      identifier: minimum
                      templateName: Inspector-TextEditor
                      label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                      propertyPath: options.minimum
                      propertyValidators:
                        10: Integer
                      additionalElementPropertyPaths:
                        10: properties.fluidAdditionalAttributes.min
                    300:
                      identifier: maximum
                      templateName: Inspector-TextEditor
                      label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                      propertyPath: options.maximum
                      propertyValidators:
                        10: Integer
                      additionalElementPropertyPaths:
                        10: properties.fluidAdditionalAttributes.max
                    9999:
                      identifier: removeButton
                      templateName: Inspector-RemoveElementEditor
                80:
                  identifier: RegularExpression
                  editors:
                    100:
                      identifier: header
                      templateName: Inspector-CollectionElementHeaderEditor
                      label: formEditor.elements.TextMixin.validators.RegularExpression.editor.header.label
                    200:
                      identifier: regex
                      templateName: Inspector-TextEditor
                      label: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.label
                      fieldExplanationText: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.fieldExplanationText
                      propertyPath: options.regularExpression
                      propertyValidators:
                        10: NotEmpty
                    9999:
                      identifier: removeButton
                      templateName: Inspector-RemoveElementEditor
          implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
          properties:
            containerClassAttribute: input
            elementClassAttribute: ''
            elementErrorClassAttribute: error
        SelectionMixin:
          formEditor:
            editors:
              100:
                identifier: header
                templateName: Inspector-FormElementHeaderEditor
              200:
                identifier: label
                templateName: Inspector-TextEditor
                label: formEditor.elements.FormElement.editor.label.label
                propertyPath: label
              300:
                identifier: options
                templateName: Inspector-PropertyGridEditor
                label: formEditor.elements.SelectionMixin.editor.options.label
                propertyPath: properties.options
                isSortable: true
                enableAddRow: true
                enableDeleteRow: true
                removeLastAvailableRowFlashMessageTitle: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageTitle
                removeLastAvailableRowFlashMessageMessage: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageMessage
              700:
                identifier: gridColumnViewPortConfiguration
                templateName: Inspector-GridColumnViewPortConfigurationEditor
                label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                configurationOptions:
                  viewPorts:
                    10:
                      viewPortIdentifier: xs
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                    20:
                      viewPortIdentifier: sm
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                    30:
                      viewPortIdentifier: md
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                    40:
                      viewPortIdentifier: lg
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                  numbersOfColumnsToUse:
                    label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                    propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                    fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
              800:
                identifier: requiredValidator
                templateName: Inspector-RequiredValidatorEditor
                label: formEditor.elements.FormElement.editor.requiredValidator.label
                validatorIdentifier: NotEmpty
                propertyPath: properties.fluidAdditionalAttributes.required
                propertyValue: required
              9999:
                identifier: removeButton
                templateName: Inspector-RemoveElementEditor
            predefinedDefaults:
              properties:
                options: {  }
          implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
          properties:
            containerClassAttribute: input
            elementClassAttribute: ''
            elementErrorClassAttribute: error
        SingleSelectionMixin:
          formEditor:
            editors:
              100:
                identifier: header
                templateName: Inspector-FormElementHeaderEditor
              200:
                identifier: label
                templateName: Inspector-TextEditor
                label: formEditor.elements.FormElement.editor.label.label
                propertyPath: label
              300:
                identifier: options
                templateName: Inspector-PropertyGridEditor
                label: formEditor.elements.SelectionMixin.editor.options.label
                propertyPath: properties.options
                isSortable: true
                enableAddRow: true
                enableDeleteRow: true
                removeLastAvailableRowFlashMessageTitle: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageTitle
                removeLastAvailableRowFlashMessageMessage: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageMessage
                shouldShowPreselectedValueColumn: single
                multiSelection: false
              700:
                identifier: gridColumnViewPortConfiguration
                templateName: Inspector-GridColumnViewPortConfigurationEditor
                label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                configurationOptions:
                  viewPorts:
                    10:
                      viewPortIdentifier: xs
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                    20:
                      viewPortIdentifier: sm
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                    30:
                      viewPortIdentifier: md
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                    40:
                      viewPortIdentifier: lg
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                  numbersOfColumnsToUse:
                    label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                    propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                    fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
              800:
                identifier: requiredValidator
                templateName: Inspector-RequiredValidatorEditor
                label: formEditor.elements.FormElement.editor.requiredValidator.label
                validatorIdentifier: NotEmpty
                propertyPath: properties.fluidAdditionalAttributes.required
                propertyValue: required
              9999:
                identifier: removeButton
                templateName: Inspector-RemoveElementEditor
            predefinedDefaults:
              properties:
                options: {  }
          implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
          properties:
            containerClassAttribute: input
            elementClassAttribute: ''
            elementErrorClassAttribute: error
        MultiSelectionMixin:
          formEditor:
            editors:
              100:
                identifier: header
                templateName: Inspector-FormElementHeaderEditor
              200:
                identifier: label
                templateName: Inspector-TextEditor
                label: formEditor.elements.FormElement.editor.label.label
                propertyPath: label
              300:
                identifier: options
                templateName: Inspector-PropertyGridEditor
                label: formEditor.elements.SelectionMixin.editor.options.label
                propertyPath: properties.options
                isSortable: true
                enableAddRow: true
                enableDeleteRow: true
                removeLastAvailableRowFlashMessageTitle: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageTitle
                removeLastAvailableRowFlashMessageMessage: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageMessage
                shouldShowPreselectedValueColumn: multiple
                multiSelection: true
              700:
                identifier: gridColumnViewPortConfiguration
                templateName: Inspector-GridColumnViewPortConfigurationEditor
                label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                configurationOptions:
                  viewPorts:
                    10:
                      viewPortIdentifier: xs
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                    20:
                      viewPortIdentifier: sm
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                    30:
                      viewPortIdentifier: md
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                    40:
                      viewPortIdentifier: lg
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                  numbersOfColumnsToUse:
                    label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                    propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                    fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
              800:
                identifier: requiredValidator
                templateName: Inspector-RequiredValidatorEditor
                label: formEditor.elements.FormElement.editor.requiredValidator.label
                validatorIdentifier: NotEmpty
                propertyPath: properties.fluidAdditionalAttributes.required
                propertyValue: required
              900:
                identifier: validators
                templateName: Inspector-ValidatorsEditor
                label: formEditor.elements.MultiSelectionMixin.editor.validators.label
                selectOptions:
                  10:
                    value: ''
                    label: formEditor.elements.MultiSelectionMixin.editor.validators.EmptyValue.label
                  20:
                    value: Count
                    label: formEditor.elements.MultiSelectionMixin.editor.validators.Count.label
              9999:
                identifier: removeButton
                templateName: Inspector-RemoveElementEditor
            predefinedDefaults:
              properties:
                options: {  }
            propertyCollections:
              validators:
                10:
                  identifier: Count
                  editors:
                    100:
                      identifier: header
                      templateName: Inspector-CollectionElementHeaderEditor
                      label: formEditor.elements.MultiSelectionMixin.validators.Count.editor.header.label
                    200:
                      identifier: minimum
                      templateName: Inspector-TextEditor
                      label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                      propertyPath: options.minimum
                      propertyValidators:
                        10: Integer
                    300:
                      identifier: maximum
                      templateName: Inspector-TextEditor
                      label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                      propertyPath: options.maximum
                      propertyValidators:
                        10: Integer
                    9999:
                      identifier: removeButton
                      templateName: Inspector-RemoveElementEditor
          implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement
          properties:
            containerClassAttribute: input
            elementClassAttribute: ''
            elementErrorClassAttribute: error
        FileUploadMixin:
          formEditor:
            editors:
              100:
                identifier: header
                templateName: Inspector-FormElementHeaderEditor
              200:
                identifier: label
                templateName: Inspector-TextEditor
                label: formEditor.elements.FormElement.editor.label.label
                propertyPath: label
              400:
                identifier: saveToFileMount
                templateName: Inspector-SingleSelectEditor
                label: formEditor.elements.FileUploadMixin.editor.saveToFileMount.label
                propertyPath: properties.saveToFileMount
                selectOptions:
                  10:
                    value: '1:/user_upload/'
                    label: '1:/user_upload/'
              700:
                identifier: gridColumnViewPortConfiguration
                templateName: Inspector-GridColumnViewPortConfigurationEditor
                label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                configurationOptions:
                  viewPorts:
                    10:
                      viewPortIdentifier: xs
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                    20:
                      viewPortIdentifier: sm
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                    30:
                      viewPortIdentifier: md
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                    40:
                      viewPortIdentifier: lg
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                  numbersOfColumnsToUse:
                    label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                    propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                    fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
              800:
                identifier: requiredValidator
                templateName: Inspector-RequiredValidatorEditor
                label: formEditor.elements.FormElement.editor.requiredValidator.label
                validatorIdentifier: NotEmpty
                propertyPath: properties.fluidAdditionalAttributes.required
                propertyValue: required
              9999:
                identifier: removeButton
                templateName: Inspector-RemoveElementEditor
            predefinedDefaults:
              properties:
                saveToFileMount: '1:/user_upload/'
          implementationClassName: TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload
          properties:
            containerClassAttribute: input
            elementClassAttribute: ''
            elementErrorClassAttribute: error
            saveToFileMount: '1:/user_upload/'
        RemoveButtonMixin:
          9999:
            identifier: removeButton
            templateName: Inspector-RemoveElementEditor
        RemovableFormElementMixin:
          editors:
            9999:
              identifier: removeButton
              templateName: Inspector-RemoveElementEditor
        BaseCollectionEditorsMixin:
          100:
            identifier: header
            templateName: Inspector-CollectionElementHeaderEditor
            label: ''
          9999:
            identifier: removeButton
            templateName: Inspector-RemoveElementEditor
        MinimumMaximumEditorsMixin:
          200:
            identifier: minimum
            templateName: Inspector-TextEditor
            label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
            propertyPath: options.minimum
            propertyValidators:
              10: Integer
          300:
            identifier: maximum
            templateName: Inspector-TextEditor
            label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
            propertyPath: options.maximum
            propertyValidators:
              10: Integer
        formEmailFinisherMixin:
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
      finishersEmailMixin:
        implementationClassName: TYPO3\CMS\Form\Domain\Finishers\EmailFinisher
        options:
          templatePathAndFilename: 'EXT:form/Resources/Private/Frontend/Templates/Finishers/Email/{@format}.html'
      FormEngineEmailMixin:
        label: tt_content.finishersDefinition.EmailToSender.label
        elements:
          subject:
            label: tt_content.finishersDefinition.EmailToSender.subject.label
            config:
              type: input
          recipientAddress:
            label: tt_content.finishersDefinition.EmailToSender.recipientAddress.label
            config:
              type: input
              eval: required
          recipientName:
            label: tt_content.finishersDefinition.EmailToSender.recipientName.label
            config:
              type: input
          senderAddress:
            label: tt_content.finishersDefinition.EmailToSender.senderAddress.label
            config:
              type: input
              eval: required
          senderName:
            label: tt_content.finishersDefinition.EmailToSender.senderName.label
            config:
              type: input
          replyToAddress:
            label: tt_content.finishersDefinition.EmailToSender.replyToAddress.label
            config:
              type: input
          carbonCopyAddress:
            label: tt_content.finishersDefinition.EmailToSender.carbonCopyAddress.label
            config:
              type: input
          blindCarbonCopyAddress:
            label: tt_content.finishersDefinition.EmailToSender.blindCarbonCopyAddress.label
            config:
              type: input
          format:
            label: tt_content.finishersDefinition.EmailToSender.format.label
            config:
              type: select
              renderType: selectSingle
              minitems: 1
              maxitems: 1
              size: 1
              items:
                10:
                  - tt_content.finishersDefinition.EmailToSender.format.1
                  - html
                20:
                  - tt_content.finishersDefinition.EmailToSender.format.2
                  - plaintext
    formManager:
      dynamicRequireJsModules:
        app: TYPO3/CMS/Form/Backend/FormManager
        viewModel: TYPO3/CMS/Form/Backend/FormManager/ViewModel
      stylesheets:
        100: 'EXT:form/Resources/Public/Css/form.css'
      translationFile: 'EXT:form/Resources/Private/Language/Database.xlf'
      javaScriptTranslationFile: 'EXT:form/Resources/Private/Language/locallang_formManager_javascript.xlf'
      selectablePrototypesConfiguration:
        100:
          identifier: standard
          label: formManager.selectablePrototypesConfiguration.standard.label
          newFormTemplates:
            100:
              templatePath: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/BlankForm.yaml'
              label: formManager.selectablePrototypesConfiguration.standard.newFormTemplates.blankForm.label
            200:
              templatePath: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/SimpleContactForm.yaml'
              label: formManager.selectablePrototypesConfiguration.standard.newFormTemplates.simpleContactForm.label
      controller:
        deleteAction:
          errorTitle: formManagerController.deleteAction.error.title
          errorMessage: formManagerController.deleteAction.error.body
