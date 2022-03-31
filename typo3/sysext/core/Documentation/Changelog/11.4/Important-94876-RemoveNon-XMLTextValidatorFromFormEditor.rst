.. include:: /Includes.rst.txt

====================================================================
Important: #94876 - Remove "Non-XML text" validator from form editor
====================================================================

See :issue:`94876`

Description
===========

The "Non-XML text" validator has been removed from the UI of the form editor.
Here's why:

* The validator has a very specific purpose since it is only useful for values
  which are output in an HTML context without escaping. By default, this is never
  the case in TYPO3 thanks to the automatic escaping in Fluid.
* The form editor is meant to be used by editors and integrators where the
  most use cases involve output of form values within TYPO3 (website / mail). This
  validator does not serve any purpose then.
* The form editor should be uncluttered and stripped from too technical and
  complex concepts which this validator belongs to.

If there are already text validators within a form definition, the UI keeps the
corresponding validator editors. I.e. the form editor will display them. In newly
created forms, the text validator can no longer be added by default.

If you want to re-add this validator just extend your own form configuration.
The following example adds the "Non-XML text" validator to the form element
`Text`. The path :yaml:`TYPO3.CMS.Form.prototypes.standard.formElementsDefinition.Text.formEditor.editors.900`
contains the definition for the validators. We are adding the validator with the key
`100` to not interfere with keys already taken by the core (`10` to `90`).

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              formElementsDefinition:
                Text:
                  formEditor:
                    editors:
                      900:
                        selectOptions:
                          100:
                            value: Text
                            label: formEditor.elements.TextMixin.editor.validators.Text.label

.. index:: Backend, ext:form
