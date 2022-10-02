.. include:: /Includes.rst.txt

.. _deprecation-95456:

=====================================================
Deprecation: #95456 - Deprecate legacy form templates
=====================================================

See :issue:`95456`

Description
===========

Using the legacy form template / partial variants residing in
:file:`EXT:form/Resources/Private/Frontend/Templates` and
:file:`EXT:form/Resources/Private/Frontend/Partials`
is deprecated. The legacy templates will be removed in v13.

Impact
======

No deprecation is logged since it would flood the logs.

Affected Installations
======================

Installations using custom templates for form elements.

Migration
=========

Set your form rendering option "templateVariant" within the form setup from
"version1" to "version2" to use the future default templates.

..  code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              formElementsDefinition:
                Form:
                  renderingOptions:
                    templateVariant: version2

Migrate your templates / partials to make them compatible with the ones stored in
:file:`EXT:form/Resources/Private/FrontendVersion2`.

.. index:: Frontend, NotScanned, ext:form
