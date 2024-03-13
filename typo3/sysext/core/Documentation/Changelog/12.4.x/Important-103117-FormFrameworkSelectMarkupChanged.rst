.. include:: /Includes.rst.txt

.. _important-103392-1710345611:

=========================================================
Important: #103392 - Form framework select markup changed
=========================================================

See :issue:`103392`

Description
===========

With :issue:`103117`, the `elementClassAttribute` of the "SingleSelect",
"CountrySelect" and "MultiSelect" fields got changed from `form-control` to
`form-select` in EXT:form, as defined by `Bootstrap`_, if the Bootstrap 5 markup
(:yaml:`templateVariant: version2`) is used.

If needed, the old markup can be restored by overriding the configuration as
follows:

..  code-block:: yaml
    :emphasize-lines: 9,15,21

    prototypes:
      standard:
        formElementsDefinition:
          CountrySelect:
            variants:
              -
                identifier: template-variant
                properties:
                  elementClassAttribute: form-control
          MultiSelect:
            variants:
              -
                identifier: template-variant
                properties:
                  elementClassAttribute: form-control
          SingleSelect:
            variants:
              -
                identifier: template-variant
                properties:
                  elementClassAttribute: form-control

.. _Bootstrap: https://getbootstrap.com/docs/5.3/forms/select/

.. index:: Frontend, ext:form
