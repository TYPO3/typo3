..  include:: /Includes.rst.txt

..  _breaking-106964-1750837443:

==========================================================================================
Breaking: #106964 - Enable "Light/Dark Mode" context awareness for CKEditor RTE by default
==========================================================================================

See :issue:`106964`

Description
===========

With :issue:`105640`, context awareness for the CKEditor Rich Text Editor (RTE)
was introduced, allowing the editor interface to automatically adapt to the
user’s system-wide light or dark mode preference.

This feature is now enabled by default. The TYPO3 Core stylesheet
:file:`EXT:rte_ckeditor/Resources/Public/Css/contents.css` has been updated to
support light and dark mode variants automatically. Previously fixed white
backgrounds now adapt dynamically based on the editor’s preferred color scheme.

Note that this change affects only the backend editor interface. The display of
RTE content in the frontend remains unaffected.

Impact
======

The previously fixed *light mode* user interface of the CKEditor RTE is now
context-aware, displaying content in light or dark mode according to the
editor’s system preference.

Affected installations
======================

TYPO3 installations that rely on a fixed *light mode* presentation of CKEditor
RTE instances in the backend are affected.

Migration
=========

Installations with custom CKEditor modifications should review their
:file:`contents.css` file.
If the TYPO3 Core default stylesheet was previously used, and a fixed *light
mode* appearance is desired, this can be enforced in the RTE YAML
configuration:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/RTE/MyCKPreset.yaml

    editor.config.contentsCss:
      - "EXT:my_extension/Resources/Public/Css/CustomContents.css"

..  index:: Backend, NotScanned
