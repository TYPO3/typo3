.. include:: /Includes.rst.txt

.. _deprecation-97016:

==================================================================================
Deprecation: #97016 - Deprecate usage of RegularExpressionValidator in form editor
==================================================================================

See :issue:`97016`

Description
===========

Enabling the RegularExpressionValidator within the form editor has been marked
as deprecated. This configurable validator will be removed from the UI in TYPO3 v13.
The goal is to unclutter and strip the form editor from too technical and complex
concepts. Regular expressions are difficult to understand especially for our
main target group, which are editors.

An according comment has been added to the configuration files of the form
framework.

Impact
======

No deprecation will be logged. The extension scanner will not report anything.
The validator will be removed from the UI in TYPO3 v13 with an according breaking
change. The impact itself is quite low, since the validator can
easily be re-added.

Affected Installations
======================

All TYPO3 installations with default form configurations (which is mostly the
case).

Migration
=========

To keep the RegularExpressionValidator within the form editor even after it has
been removed in TYPO3 v13 you can re-add it if needed. This can be done by extending
form configurations.

The following example adds the validator to the form element `Text`. The path
:yaml:`TYPO3.CMS.Form.prototypes.standard.formElementsDefinition.Text.formEditor.editors.900`
contains the definition for validators. We are adding the validator with the key
`200` to not interfere with keys already taken by the Core.

..  code-block:: yaml

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
                          200:
                            value: RegularExpression
                            label: formEditor.elements.TextMixin.editor.validators.RegularExpression

.. index:: Backend, NotScanned, ext:form
