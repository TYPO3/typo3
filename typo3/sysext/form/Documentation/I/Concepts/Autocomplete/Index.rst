.. include:: /Includes.rst.txt

.. _concepts-autocomplete:

============
Autocomplete
============

The :guilabel:`Autocomplete` select in the form editor can be used to
define :html:`autocomplete` properties for input fields. This extension
predefines the most common of the input purposes that are widely
recognized by assistive technologies and
`recommended by the W3C <https://www.w3.org/TR/WCAG21/#input-purposes>`__. The
HTML standard allows arbitrary values.

If you need to provide additional fields, you can reconfigure the autocomplete
field with additional select options:

.. _concepts-autocomplete-add-options:

Add Autocomplete options to the backend editor
==============================================

Extend the EXT:form configuration:

..  literalinclude:: _setup.typoscript
    :language: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

Redefine the backend input in the extended YAML:

..  literalinclude:: _CustomFormSetupAutoCompleteOption.yaml
    :language: yaml
    :caption: EXT:my_sitepackage/Configuration/Form/CustomFormSetup.yaml
