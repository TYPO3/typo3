..  include:: /Includes.rst.txt

..  _breaking-106964-1750837443:

==========================================================================================
Breaking: #106964 - Enable "Light/Dark Mode" context awareness for CKEditor RTE by default
==========================================================================================

See :issue:`106964`

Description
===========


With :issue:`105640` context awareness for CKEditor RTE was introduced, but not enabled
by default to not break existing customizations/enhancements of the editor interface.

The TYPO3 Core default stylesheet (`EXT:rte_ckeditor/Resources/Public/Css/contents.css`)
has now been adapted to enable the context awareness by default. Prior white backgrounds
are now shown as either dark or light backgrounds, depending on the editor's preference.

Please note that the display of RTE contents in the frontend is unaffected.

Impact
======

Prior fixed "light mode" UI display is now made context aware, showing CKEditor RTE
instance contents in the editor's preferred dark/light mode variant.

Affected installations
======================

TYPO3 installations relying on "light mode" UI presentation of the CKEditor RTE
instances in the backend.


Migration
=========

Installations with custom CKEditor changes should review their `contents.css` file.
If previously the TYPO3 Core default sheet was set, and a fixed "light mode" is
preferred, this can be achieved by adjusting the RTE YAML configuration:

    ..  code-block:: yaml
        :caption: MyCKPreset.yaml

        editor.config.contentsCss:
          - "EXT:my_extension/Resources/Public/Css/CustomContents.css"

..  index:: Backend, NotScanned
