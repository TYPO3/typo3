.. include:: /Includes.rst.txt

.. _important-102507-1702317316:

================================================================================================
Important: #102507 - Default CKEditor5 allowed classes and data attributes configurated reverted
================================================================================================

See :issue:`102507`

Description
===========

With TYPO3 v12.4.7 (see :issue:`99738`) an option to allow all classes in
CKEditor5 has been enabled in the TYPO3 default configuration which implicitly
caused all custom html elements to be allowed. This rule has now been dropped
from the default configuration:

..  code-block:: yaml

    htmlSupport:
      allow:
        - { classes: true, attributes: { pattern: 'data-.+' } }

The configuration matched to any HTML element available in the CKEditor5 General
HTML Support (GHS) schema definition.
This became an issue, since CKEditor5 relies on the set of allowed elements and
classes when processing content that is pasted from Microsoft Office.

Installations that relied on the fact that v12.4.7 allowed all CSS classes in
CKEditor5 should encode the set of available style definitions via
`editor.config.style.definitions` which will make them accessible to editors
via the style dropdown toolbar element:

..  code-block:: yaml

    style:
      definitions:
         - { name: "Descriptive Label", element: "p", classes: ['my-class'] }


Custom data attributes can be allowed via General HTML Support:

..  code-block:: yaml

    htmlSupport:
      allow:
        - { name: 'div', attributes: ['data-foobar'] }

.. index:: RTE, YAML, ext:rte_ckeditor
