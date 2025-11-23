..  include:: /Includes.rst.txt

..  _breaking-106596-1746896335:

================================================
Breaking: #106596 - Remove legacy form templates
================================================

See :issue:`106596`

Description
===========

In earlier TYPO3 versions, the Form Framework provided two template variants
for frontend rendering:

-   The initial, legacy templates in
    :file:`EXT:form/Resources/Private/Frontend/Templates` and
    :file:`EXT:form/Resources/Private/Frontend/Partials`, which were deprecated
    in :issue:`95456`
-   The newer, Bootstrap 5 compatible and accessible templates in
    :file:`EXT:form/Resources/Private/FrontendVersion2/Templates` and
    :file:`EXT:form/Resources/Private/FrontendVersion2/Partials`, introduced
    with :issue:`94868`

The legacy form templates have now been removed. The rendering option
:yaml:`templateVariant`, which toggled the template and form configuration
variant, has been removed as well.

The newer template variants have been moved to the original file paths of the
legacy templates.

Impact
======

The removal of the legacy templates and the :yaml:`templateVariant`
configuration option simplifies the Form Framework rendering logic.

Developers no longer need to choose between multiple template variants,
reducing complexity and improving maintainability. Projects already using the
newer templates benefit from a cleaner configuration and a unified rendering
approach.

Affected installations
======================

All TYPO3 installations using the Form Framework are affected.

Migration
=========

If you still rely on the legacy templates, you must migrate your templates and
partials to the structure of the newer templates.

Websites that use :yaml:`templateVariant: version2` can simplify their form
configuration. Variants with the condition
:yaml:`'getRootFormProperty("renderingOptions.templateVariant") == "version2"'`
are no longer necessary and can be removed.

**Before:**

..  code-block:: yaml

    prototypes:
      standard:
        formElementsDefinition:
          Text:
            variants:
              -
                identifier: template-variant
                condition: 'getRootFormProperty("renderingOptions.templateVariant") == "version2"'
                properties:
                  containerClassAttribute: 'form-element form-element-text mb-3'
                  elementClassAttribute: form-control
                  elementErrorClassAttribute: ~
                  labelClassAttribute: form-label

**After:**

..  code-block:: yaml

    prototypes:
      standard:
        formElementsDefinition:
          Text:
            properties:
              containerClassAttribute: 'form-element form-element-text mb-3'
              elementClassAttribute: form-control
              elementErrorClassAttribute: ~
              labelClassAttribute: form-label

..  index:: Frontend, YAML, NotScanned, ext:form
