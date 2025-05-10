..  include:: /Includes.rst.txt

..  _breaking-106596-1746896335:

================================================
Breaking: #106596 - Remove legacy form templates
================================================

See :issue:`106596`

Description
===========

Until now, the Form Framework provided two template variants for frontend rendering:

- The initial, legacy templates in :file:`EXT:form/Resources/Private/Frontend/Templates` and
  :file:`EXT:form/Resources/Private/Frontend/Partials`, which have been deprecated in :issue:`95456`
- The newer, Bootstrap 5 compatible and accessible templates in :file:`EXT:form/Resources/Private/FrontendVersion2/Templates`
  and :file:`EXT:form/Resources/Private/FrontendVersion2/Partials`, introduced with :issue:`94868`

The legacy form templates are now removed. The form rendering option :yaml:`templateVariant`
which toggled the use of templates and form configuration is removed as well.

The newer form template variants have been moved to the file paths of the initial templates.

Impact
======

The removal of the legacy templates and the :yaml:`templateVariant` configuration
option simplifies the Form Framework’s rendering logic.

Developers no longer need to choose between multiple template variants,
reducing complexity and improving maintainability. Projects already using the
newer templates benefit from a cleaner configuration and a unified rendering approach.

Affected installations
======================

All TYPO3 installations using the Form Framework are affected.


Migration
=========

If you still rely on the legacy templates, you will now need to migrate your
templates / partials to make them compatible with the newer template structure.

Websites that use :yaml:`templateVariant: version2` can now simplify their form configuration.
The form variants with condition :yaml:`'getRootFormProperty("renderingOptions.templateVariant") == "version2"'`
are no longer necessary and can be removed.

Before:

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

After:

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
