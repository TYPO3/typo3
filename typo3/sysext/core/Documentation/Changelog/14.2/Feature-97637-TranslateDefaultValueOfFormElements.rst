..  include:: /Includes.rst.txt

..  _feature-97637-1772528713:

==========================================================
Feature: #97637 - Translate default value of form elements
==========================================================

See :issue:`97637`

Description
===========

The TYPO3 form framework now supports translating the
:yaml:`defaultValue` property of form elements via XLF translation files.

Previously only properties such as :yaml:`label`, :yaml:`placeholder`, and
rendering options could be translated through the form framework's translation
mechanism. The :yaml:`defaultValue` property was always rendered as-is from the
form definition, regardless of the current frontend language.

Now, :yaml:`defaultValue` is translated before rendering using the
same XLF key conventions already established for other form element
properties. The translation is resolved in the following order, with the first
match winning:

#.  `<form-definition-identifier>.element.<element-identifier>.properties.defaultValue`
#.  `element.<element-identifier>.properties.defaultValue`
#.  `element.<element-type>.properties.defaultValue`

Example
=======

Given a form element defined as follows:

..  code-block:: yaml
    :caption: fileadmin/form_definitions/contact.form.yaml

    identifier: contact-form
    type: Form
    prototypeName: standard
    renderables:
      - type: Page
        identifier: page-1
        renderables:
          - type: Text
            identifier: bestDish
            label: 'Best dish?'
            defaultValue: 'Hamburger'
            renderingOptions:
              translation:
                translationFiles:
                  - 'EXT:my_extension/Resources/Private/Language/Form/locallang.xlf'

The translation file can now provide a translated default value:

..  code-block:: xml
    :caption: EXT:my_extension/Resources/Private/Language/Form/de.locallang.xlf

    <?xml version="1.0" encoding="utf-8"?>
    <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
        <file source-language="en" target-language="de" datatype="plaintext" original="messages">
            <body>
                <trans-unit id="contact-form.element.bestDish.properties.defaultValue">
                    <source>Hamburger</source>
                    <target>Kartoffel</target>
                </trans-unit>
            </body>
        </file>
    </xliff>

Or, to apply the translation to all :yaml:`Text` elements across all forms:

..  code-block:: xml
    :caption: EXT:my_extension/Resources/Private/Language/Form/de.locallang.xlf

    <trans-unit id="element.Text.properties.defaultValue">
        <source>Hamburger</source>
        <target>Kartoffel</target>
    </trans-unit>

Impact
======

Form integrators can now provide language-specific default values for form
elements. The translation is applied automatically before rendering using the
existing translation file configuration in
:yaml:`renderingOptions.translation.translationFiles`.

Array-based :yaml:`defaultValue` properties are intentionally excluded from
translation. These occur in multi-value elements such as
:yaml:`MultiCheckbox` or :yaml:`MultiSelect`, where the values are option keys
that must match the configured :yaml:`properties.options` exactly.
Translating them would break the value-to-option mapping. The option labels
themselves can be translated via the existing
:yaml:`properties.options.[*]` translation mechanism.

..  index:: Frontend, ext:form
