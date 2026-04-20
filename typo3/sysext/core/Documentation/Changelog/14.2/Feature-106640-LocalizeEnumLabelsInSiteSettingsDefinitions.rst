..  include:: /Includes.rst.txt

..  _feature-106640-1766572100:

====================================================================
Feature: #106640 - Localize enum labels in site settings definitions
====================================================================

See :issue:`106640`

Description
===========

Enum option labels in site settings definitions can now be localized
consistently.

This applies to all common enum declaration styles:

*   List-style enum declarations derive localization keys using
    :code:`settings.<settingKey>.enum.<enumValue>` in the set labels file.
*   Map-style enum declarations are independent of that key schema. Only
    the configured label value is evaluated.
*   Map-style enum declarations with localization references
    (:code:`LLL:...`) resolve these references.
*   Map-style enum declarations with literal labels keep these labels
    as-is.
*   Map-style key-only enum entries fall back to the enum value.
*   Map-style empty string labels remain empty strings.

Example
=======

..  code-block:: yaml
    :caption: List-style enum declaration in settings.definitions.yaml

    settings:
      my.enumSetting:
        type: string
        default: optionA
        enum:
          - optionA
          - optionB

..  code-block:: xml
    :caption: Matching labels in labels.xlf

    <trans-unit id="settings.my.enumSetting.enum.optionA">
      <source>Option A (localized)</source>
    </trans-unit>
    <trans-unit id="settings.my.enumSetting.enum.optionB">
      <source>Option B (localized)</source>
    </trans-unit>

..  code-block:: yaml
    :caption: Map-style enum declaration in settings.definitions.yaml

    settings:
      my.enumSetting:
        type: string
        default: optionA
        enum:
          optionA: 'LLL:my_extension.labels:settings.custom.optionA' # Explicit LLL reference
          optionB: 'Literal Option B' # Literal label
          optionC: # Key-only map-style entry, falls back to enum value "optionC"
          optionD: '' # Empty label stays empty

..  code-block:: xml
    :caption: Referenced label in labels.xlf

    <trans-unit id="settings.custom.optionA">
      <source>Option A (localized)</source>
    </trans-unit>

If you want to work with automatically derived keys in the set
:file:`labels.xlf`, for example
:code:`settings.<settingKey>.enum.<enumValue>`, omit enum labels in YAML
and use list-style enum declarations.

Impact
======

Integrators can localize enum options consistently using the same
resolution behavior as other setting labels.

..  index:: YAML, Backend, ext:core
