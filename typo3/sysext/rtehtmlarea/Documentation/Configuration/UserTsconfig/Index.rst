.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _user-tsconfig:

User TSconfig
-------------

Upon installation, the extension will set default properties in User
TSconfig as specified by the extension configuration variable:
**Default configuration settings**. Three default configurations are
available: Minimal, Typical, and Demo. These default configurations
are documented in the next section of the present document.

These properties may be modified for any particular BE user or BE user
group, with the TYPO3 User Admin Tool. Properties of User TSconfig are
documented in the :ref:`TSconfig reference <t3tsconfig:usertsconfig>`.


.. _setup-default-edit-rte:

setup.default.edit\_RTE
^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.default.edit\_RTE

   Data type
         boolean

   Description
         Specifies that RTE editing should be enabled or disabled by default.



.. _setup-override-edit-rte:

setup.override.edit\_RTE
^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.override.edit\_RTE

   Data type
         boolean

   Description
         Specifies that RTE editing should be enabled or disabled, the user not
         being allowed to change the setting.



.. _setup-default-rtewidth:

setup.default.rteWidth
^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.default.rteWidth

   Data type
         int+/%

   Description
         If set, specifies the default width of the RTE editing area. The
         specified value overrides the calculated width of the RTE editing
         area. Note that a percentage may be specified.

         Note: The property is ignored in IE if the value is a percentage.



.. _setup-override-rtewidth:

setup.override.rteWidth
^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.override.rteWidth

   Data type
         int+/%

   Description
         If set, specifies the width of the RTE editing area, the user not
         being allowed to change the setting. The specified value overrides the
         calculated width of the RTE editing area. Note that a percentage may
         be specified.

         Note: The property is ignored in IE if the value is a percentage.



.. _setup-default-rteheight:

setup.default.rteHeight
^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.default.rteHeight

   Data type
         int+

   Description
         If set, specifies the default height of the RTE editing area. The
         specified value overrides the calculated height of the RTE editing
         area. This includes the height of the toolbar, of the editing area and
         of the status bar.



.. _setup-override-rteheight:

setup.override.rteHeight
^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.override.rteHeight

   Data type
         int+

   Description
         If set, specifies the height of the RTE editing area, the user not
         being allowed to change the setting. The specified value overrides the
         calculated height of the RTE editing area. This includes the height of
         the toolbar, of the editing area and of the status bar.



.. _setup-default-rteresize:

setup.default.rteResize
^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.default.rteResize

   Data type
         boolean

   Description
         Specifies whether or not the RTE is resizable by default.



.. _setup-override-rteresize:

setup.override.rteResize
^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.override.rteResize

   Data type
         boolean

   Description
         Specifies whether or not the RTE is resizable, the user not being
         allowed to change the setting.



.. _setup-default-rtemaxheight:

setup.default.rteMaxHeight
^^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.default.rteMaxHeight

   Data type
         int+

   Description
         If set, and if the RTE is resizable, specifies the default maximal
         height of the RTE, including the tool bar, the editing area and the
         status bar.



.. _setup-override-rtemaxheight:

setup.override.rteMaxHeight
^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.override.rteMaxHeight

   Data type
         int+

   Description
         If set, and if the RTE is resizable, specifies the default maximal
         height of the RTE, including the tool bar, the editing area and the
         status bar, the user not being allowed to change the setting.



.. _setup-default-rtecleanpastebehaviour:

setup.default.rteCleanPasteBehaviour
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.default.rteCleanPasteBehaviour

   Data type
         string

   Description
         If set, specifies the default clean paste behaviour when the
         pastetoggle button is ON.

         Possible values are: plainText, pasteStructure, pasteFormat.



.. _setup-override-rtecleanpastebehaviour:

setup.override.rteCleanPasteBehaviour
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         setup.override.rteCleanPasteBehaviour

   Data type
         string

   Description
         If set, specifies the clean paste behaviour when the pastetoggle
         button is ON, the user not being allowed to change the setting

         Possible values are: plainText, pasteStructure, pasteFormat.



.. _options-rtekeylist:

options.RTEkeyList
^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         options.RTEkeyList

   Data type
         list of id-strings

   Description
         Specifies the list of RTE buttons to which the BE user or BE user
         group is restricted.

         Default: \* (means all)

         Note: For the list of possible buttons, see property showButtons of
         Page TsConfig.



.. _options-htmlareapspellmode:

options.HTMLAreaPspellMode
^^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         options.HTMLAreaPspellMode

   Data type
         string

   Description
         Specifies the mode of spelling suggestions. Possible values are:
         ultra, fast, normal or bad-spellers.

         Default: normal

         Note: For more information on spelling suggestions modes, see `Notes
         on the Different Suggestion Modes <http://aspell.net/man-html/Notes-
         on-the-Different-Suggestion-
         Modes.html#Notes%20on%20the%20Different%20Suggestion%20Modes>`_ ).



.. _options-enablepersonaldicts:

options.enablePersonalDicts
^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         options.enablePersonalDicts

   Data type
         boolean

   Description
         Enables the personal dictionaries feature for the user or user group,
         when the feature is enabled in Page TSconfig.

         Default: 0

         Note: The feature must also be enabled in Page TSconfig.



.. _options-uploadfieldsintopofeb:

options.uploadFieldsInTopOfEB
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         options.uploadFieldsInTopOfEB

   Data type
         boolean

   Description
         Inserts a file uploader on the 'file' tab of the Insert/Modify link
         dialogue as well as on the magic, plain and dragdrop tabs of the
         Insert/modify image dialogue.

         Note: This applies only when buttons.link.TYPO3Browser.disabled and/or
         buttons.image.TYPO3Browser.disabled is not set.

         Default: 0



.. _options-createfoldersineb:

options.createFoldersInEB
^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         options.createFoldersInEB

   Data type
         boolean

   Description
         If set, a create folders option appears in the TYPO3 file browser.

         Note: This applies only when buttons.link.TYPO3Browser.disabled and/or
         buttons.image.TYPO3Browser.disabled is not set.

         Note: For admin-users this is always enabled.

         Default: 0



.. _options-nothumbsinrteimageselect:

options.noThumbsInRTEimageSelect
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         options.noThumbsInRTEimageSelect

   Data type
         boolean

   Description
         If set, then image thumbnails are not shown in the image selector.

         Default: 0



.. _options-rtelargewidthincrement:

options.RTELargeWidthIncrement
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         options.RTELargeWidthIncrement

         options.RTELargeHeightIncrement

   Data type
         pixels

   Description
         Increments applied to the width and height of the editor area

         Default: RTELargeWidthIncrement= 150, RTELargeHeilghtIncrement = 0



.. _page-rte-default-buttons-formatblock-restricttoitems:

page.RTE.default.buttons.formatblock.restrictToItems
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         page.RTE.default.buttons.formatblock.restrictToItems

   Data type
         list of id-strings

   Description
         List of options to which the user will be restricted in the block
         formating drop-down list.

         The available options are: p, h1, h2, h3, h4, h5, h6, pre, address,
         article, aside, blockquote, div, footer, header, nav, section



.. _page-rte-default-buttons-formattext-restrictto:

page.RTE.default.buttons.formattext.restrictTo
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         page.RTE.default.buttons.formattext.restrictTo

   Data type
         list of id-strings

   Description
         Restricts the availability of options, or inline element types, in the
         text formating drop-down list.

         Default: \* (means all)


