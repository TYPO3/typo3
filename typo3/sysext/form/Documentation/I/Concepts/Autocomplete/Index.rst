..  include:: /Includes.rst.txt

..  _concepts-autocomplete:

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

..  _concepts-autocomplete-add-options:

Add Autocomplete options to the backend editor
==============================================

Create a form set in your extension and add a :file:`config.yaml` with the
additional autocomplete options. The file is auto-discovered — no PHP or
TypoScript registration is required.

..  code-block:: none
    :caption: Required directory layout

    EXT:my_sitepackage/
      Configuration/
        Form/
          SitePackage/
            config.yaml

..  literalinclude:: _config.yaml
    :language: yaml
    :caption: EXT:my_sitepackage/Configuration/Form/SitePackage/config.yaml
