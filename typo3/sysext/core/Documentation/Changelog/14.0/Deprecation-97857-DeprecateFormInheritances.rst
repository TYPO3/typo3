..  include:: /Includes.rst.txt

..  _deprecation-97857-1761224875:

=============================================================================
Deprecation: #97857 - Deprecate __inheritances operator in form configuration
=============================================================================

See :issue:`97857`

Description
===========

The :yaml:`__inheritances` operator has been deprecated.

Developers are encouraged to migrate to native YAML features such as anchors, aliases, and overrides
to avoid code duplication.


Impact
======

Using the :yaml:`__inheritances` operator inside a custom YAML form configuration
will trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected installations
=======================

All extensions and projects relying on the :yaml:`__inheritances` operator
need to update their YAML files accordingly.

Migration
=========

The custom implementation with the :yaml:`__inheritances` operator can be replaced with native YAML syntax.
You can avoid duplication in your YAML files by using anchors (&), aliases (*) and overrides (<<:).

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
