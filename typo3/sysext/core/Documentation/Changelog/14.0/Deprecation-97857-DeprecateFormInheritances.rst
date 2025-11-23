..  include:: /Includes.rst.txt

..  _deprecation-97857-1761224875:

=============================================================================
Deprecation: #97857 - Deprecate __inheritances operator in form configuration
=============================================================================

See :issue:`97857`

Description
===========

The custom :yaml:`__inheritances` operator, which was available only in
YAML configuration files of EXT:form, has been deprecated.

Previously, this operator was used within form definition files to inherit
and reuse configuration parts between form element definitions.
With native YAML functionality now providing equivalent and more flexible
features, this TYPO3-specific operator is no longer necessary.

Developers are encouraged to migrate to standard YAML features such as
anchors, aliases, and overrides to avoid code duplication and to simplify
form configuration maintenance.

Impact
======

Using the :yaml:`__inheritances` operator inside a custom YAML form configuration
in EXT:form will trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected installations
=======================

All installations with custom form definitions or form element configurations
that use the :yaml:`__inheritances` operator in their EXT:form YAML files
are affected and need to update those files accordingly.

Migration
=========

The custom TYPO3 implementation using :yaml:`__inheritances` can be replaced
with standard YAML syntax.

Developers can achieve the same result by using anchors (:yaml:`&`),
aliases (:yaml:`*`), and overrides (:yaml:`<<:`).

Before:

..  code-block:: yaml

    mixins:
      formElementMixins:
        BaseFormElementMixin:
          1761226183:
            identifier: custom
            templateName: Inspector-TextEditor
            label: Custom editor
            propertyPath: custom
        OtherBaseFormElementMixin:
          1761226184:
            identifier: otherCustom
            templateName: Inspector-TextEditor
            label: Other custom editor
            propertyPath: otherCustom

    prototypes:
      standard:
        formElementsDefinition:
          Text:
            formEditor:
              editors:
                __inheritances:
                  10: 'mixins.formElementMixins.BaseFormElementMixin'
                  20: 'mixins.formElementMixins.OtherBaseFormElementMixin'

After:

..  code-block:: yaml

    customEditor: &customEditor
      1761226183:
        identifier: custom
        templateName: Inspector-TextEditor
        label: Custom editor
        propertyPath: custom

    otherCustomEditor: &otherCustomEditor
      identifier: otherCustom
      templateName: Inspector-TextEditor
      label: Other custom editor
      propertyPath: otherCustom

    prototypes:
      standard:
        formElementsDefinition:
          Text:
            formEditor:
              editors:
                <<: *customEditor
                1761226184: *otherCustomEditor

..  index:: Backend, ext:form, NotScanned
