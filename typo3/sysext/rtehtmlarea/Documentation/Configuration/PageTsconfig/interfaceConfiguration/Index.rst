.. include:: ../../../Includes.txt
.. highlight:: typoscript


.. _rte-interface-configuration-objects:

RTE interface configuration objects:
""""""""""""""""""""""""""""""""""""

These objects contain the actual configuration of the RTE interface.


.. _default:

default.[...]
~~~~~~~~~~~~~

.. container:: table-row

   Property
         default.[...]

         config.[ *tablename* ].[ *field* ].[...]

         config.[ *tablename* ].[ *field* ].types.[ *type* ].[...]

   Description
         These objects contain the actual configuration of the RTE interface.
         For the properties available, refer to the table below.This is a
         description of how you can customize in general and override for
         specific fields/types.

         'RTE.default' configures the RTE for all tables/fields/types

         'RTE.config.[ *tablename* ].[ *field* ]' configures a specific field.
         The values inherit the values from 'RTE.default' in fact this is
         overriding values.

         'RTE.config.[ *tablename* ].[ *field* ].types.[ *type* ]' configures a
         specific field in case the 'type'-value of the field matches  *type* .
         Again this overrides the former settings.


[page:RTE]



.. _rte-interface-configuration-properties:

RTE interface configuration properties:
"""""""""""""""""""""""""""""""""""""""

These properties may be set for each RTE interface configuration
object.


.. _disabled:

disabled
~~~~~~~~

.. container:: table-row

   Property
         disabled

   Data type
         boolean

   Description
         If set, the editor is disabled.



.. _showbuttons:

showButtons
~~~~~~~~~~~

.. container:: table-row

   Property
         showButtons

   Data type
         list of id-strings

   Description
         List of buttons that should be enabled in the editor toolbar.

         Note: showButtons = \* shows all available buttons.

         Available buttons are: blockstylelabel, blockstyle, textstylelabel,
         textstyle, fontstyle, fontsize, formatblock, blockquote,
         insertparagraphbefore, insertparagraphafter, lefttoright, righttoleft,
         language, showlanguagemarks, left, center, right, justifyfull,
         orderedlist, unorderedlist, definitionlist, definitionitem, outdent,
         indent, formattext, bidioverride, big, bold, citation, code,
         definition, deletedtext, emphasis, insertedtext, italic, keyboard,
         monospaced, quotation, sample, small, span, strikethrough, strong,
         subscript, superscript, underline, variable, textcolor, bgcolor,
         textindicator, editelement, showmicrodata, emoticon, insertcharacter,
         insertsofthyphen, line, link, unlink, image, table, user, abbreviation,
         findreplace, spellcheck, chMode, inserttag, removeformat, copy, cut,
         paste, pastetoggle, pastebehaviour, undo, redo, about, toggleborders,
         tableproperties, tablerestyle, rowproperties, rowinsertabove,
         rowinsertunder, rowdelete, rowsplit, columnproperties,
         columninsertbefore, columninsertafter, columndelete, columnsplit,
         cellproperties, cellinsertbefore, cellinsertafter, celldelete,
         cellsplit, cellmerge

         Note: Buttons textcolor, bgcolor, fontstyle and fontsize are enabled
         only if «Enable features that use the style attribute> is checked in
         the extension manager.

         Note: If extension static\_info\_tables is not installed, the
         spellcheck, language and abbreviation buttons are not enabled.

         Note: If the encoding of the content element is not either iso-8859-1
         or utf-8, the spellcheck button is not enabled.

         Note: Buttons user and abbreviation are never available in the front end.

         Note: Button unlink is not available if button link is not available.

         Note: None of the table operations buttons is available if the button
         table is not available.

         Note: Firefox 29+, Opera, Safari 5+ and Chrome 6+ do not support the copy,
         cut and paste buttons.



.. _hidebuttons:

hideButtons
~~~~~~~~~~~

.. container:: table-row

   Property
         hideButtons

   Data type
         list of id-strings

   Description
         List of buttons that should not be enabled in the editor toolbar.



.. _toolbarorder:

toolbarOrder
~~~~~~~~~~~~

.. container:: table-row

   Property
         toolbarOrder

   Data type
         list of id-strings

   Description
         Specifies the order and grouping of buttons in the RTE tool bar. The
         keywords space, bar and linebreak may be used to insert a space, a
         separator or a line break at the corresponding position in the tool
         bar.

         Default: blockstylelabel, blockstyle, space, textstylelabel,
         textstyle, linebreak,

         bar, formattext, bold, strong, italic, emphasis, big, small,
         insertedtext, deletedtext, citation, code, definition, keyboard,
         monospaced, quotation, sample, variable, bidioverride, strikethrough,
         subscript, superscript, underline, span, bar, fontstyle, space,
         fontsize, bar, formatblock, blockquote, insertparagraphbefore,
         insertparagraphafter, bar, lefttoright, righttoleft, language,
         showlanguagemarks, bar, left, center, right, justifyfull, bar,
         orderedlist, unorderedlist, definitionlist, definitionitem, outdent,
         indent, bar, textcolor, bgcolor, textindicator, bar, editelement,
         showmicrodata, emoticon, insertcharacter, insertsofthyphen, line,
         link, unlink, image, table, user, abbreviation, bar, findreplace,
         spellcheck, bar, chMode, inserttag, removeformat, bar, copy, cut,
         paste, pastetoggle, pastebehaviour, bar, undo, redo, bar, about,
         linebreak, toggleborders, bar, tableproperties, tablerestyle, bar,
         rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit,
         bar, columnproperties, columninsertbefore, columninsertafter,
         columndelete, columnsplit, bar, cellproperties, cellinsertbefore,
         cellinsertafter, celldelete, cellsplit, cellmerge



.. _keepbuttongrouptogether:

keepButtonGroupTogether
~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         keepButtonGroupTogether

   Data type
         boolean

   Description
         Specifies that all buttons of a button group are displayed on the same
         line of the tool bar. A button group is delimited by a linebreak or by
         a bar.

         Default: 0

         Note: If enabled, the setting is honored only by Mozilla/Firefox and
         Safari. It is ignored when the browser is Internet Explorer, Opera or
         Mozilla 1.3.



.. _defaultcontentlanguage:

defaultContentLanguage
~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         defaultContentLanguage

   Data type
         string

   Description
         ISO code of default language of content elements. This language is
         used by RTE features that insert content, usually in the form of
         values of html tag attributes, when the language of the content
         element is not specified. This property applies to TYPO3 BE only.

         Default: en

         Note: Any value other than 'en' requires Static Info Tables to be
         installed.



.. _contextmenu-disabled:

contextMenu.disabled
~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         contextMenu.disabled

   Data type
         boolean

   Description
         If set, the context menu of the RTE triggered by mouse right click is
         disabled.

         Default: 0

         Note: Context menu is not available in Opera.



.. _contextmenu-showbuttons:

contextMenu.showButtons
~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         contextMenu.showButtons

   Data type
         list of id-strings

   Description
         List of buttons that should be shown in the context menu For the list
         of available buttons see property showButtons above.

         Default: If not specified, all buttons available in the editor toolbar
         will appear in the context menu, unless they are removed by property
         contextMenu.hideButtons.

         Note: Drop-down lists or select boxes will not be shown in the context
         menu.

         Note: The buttons must be enabled in the editor toolbar.

         Note: The buttons will appear in the same order as in the editor
         toolbar (see property toolbarOrderabove).



.. _contextmenu-hidebuttons:

contextmenu.hideButtons
~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         contextmenu.hideButtons

   Data type
         list of id-strings

   Description
         List of buttons that should not be shown in the context menu.



.. _contextmenu-maxheight:

contextMenu.maxHeight
~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         contextMenu.maxHeight

   Data type
         int+

   Description
         Maximum height of the context menu in pixels.

         Default: 300



.. _showstatusbar:

showStatusBar
~~~~~~~~~~~~~

.. container:: table-row

   Property
         showStatusBar

   Data type
         boolean

   Description
         Specifies that the editor status bar should be displayed or not.

         Default: 0

         Note: showStatusBar is set to 1 in the Typical and Demo default
         configurations (see chapter on default configurations).



.. _buttons-editelement-removefieldsets:

buttons.editelement.removeFieldsets
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.editelement.removeFieldsets

   Data type
         list of id-strings

   Description
         List of fieldsets to remove from the edit element dialogue.

         Possible string values are: identification, style, language,
         microdata, events.



.. _buttons-editelement-properties-removed:

buttons.editelement.properties.removed
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.editelement.properties.removed

   Data type
         list of id-strings

   Description
         List of fields to remove from the edit element dialogue.

         Possible string values are: id, title, language, direction, onkeydown,
         onkeypress, onkeyup, onclick, ondblclick, onmousedown, onmousemove,
         onmouseout, onmouseover, onmouseup.



.. _buttons-formatblock-orderitems:

buttons.formatblock.orderItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formatblock.orderItems

   Data type
         list of id-strings

   Description
         Specifies the order in which the block element types are presented in
         the block formating drop-down list.

         The standard block element types are: p, h1, h2, h3, h4, h5, h6, pre,
         address, article, aside, blockquote, div, footer, header, nav, section

         The list may also contain custom items as specified by the
         buttons.formatblock.addItems property.

         If not set, the default order will be alphabetical, in the language of
         the current backend user.

         Note: If set, any option not in the list will be removed from the
         drop-down list.



.. _buttons-formatblock-removeitems:

buttons.formatblock.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formatblock.removeItems

   Data type
         list of id-strings

   Description
         List of default items to be removed from the block formating drop-down
         list.

         The default items are: p, h1, h2, h3, h4, h5, h6, pre, address,
         article, aside, blockquote, div, footer, header, nav, section



.. _buttons-formatblock-additems:

buttons.formatblock.addItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formatblock.addItems

   Data type
         list of id-strings

   Description
         List of custom items to be added to the block formating drop-down
         list.

         Each of the added items should be configured.



.. _buttons-formatblock-items-item-name-label:

buttons.formatblock.items.[ *item-name* ].label
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formatblock.items.[ *item-name* ].label

   Data type
         string

   Description
         Alternative label for the option identified by the item name in the
         block formating drop-down list.

         Note: The string may be a reference to an entry in a localization file
         of the form LLL:EXT:[ *fileref* ]:[ *labelkey* ]



.. _buttons-formatblock-items-item-name-addclass:

buttons.formatblock.items.[ *item-name* ].addClass
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formatblock.items.[ *item-name* ].addClass

   Data type
         string

   Description
         A class name to be assigned to the blocks whenever the item is applied
         to selected text.

         Note: The specified class should be allowed on elements of the block
         type (using property RTE.default.buttons.blockstyle.tags.[ *tagName*
         ].allowedClasses).



.. _buttons-formatblock-items-item-name-tagname:

buttons.formatblock.items.[ *item-name* ].tagName
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formatblock.items.[ *item-name* ].tagName

   Data type
         string

   Description
         A tag name to be assigned to the block elements whenever the (custom)
         item is applied to selected text.

         The value of this property must be equal to one of the standard block
         element types.

         Note: [ *item-name* ] must not be a standard block tag name.

         Note: If [ *item-name* ] also has property addClass, then the
         specified class should be allowed on elements of block type tagName
         (using property RTE.default.buttons.blockstyle.tags.[ *tagName*
         ].allowedClasses).



.. _buttons-formatblock-prefixlabelwithtag:

buttons.formatblock.prefixLabelWithTag
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formatblock.prefixLabelWithTag

   Data type
         boolean

   Description
         If set, the option label in the block formating drop-down list is
         prefixed with the tagname.

         Default: 0



.. _buttons-formatblock-postfixlabelwithtag:

buttons.formatblock.postfixLabelWithTag
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formatblock.postfixLabelWithTag

   Data type
         boolean

   Description
         If set, the option label in the block formating drop-down list is
         postfixed with the tagname.

         Default: 0



.. _buttons-formatblock-items-item-name-hotkey:

buttons.formatblock.items.[ *item-name* ].hotKey
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formatblock.items.[ *item-name* ].hotKey

   Data type
         character

   Description
         A hotkey will be associated with the option of the block formating
         drop-down list identified by the item name.



.. _buttons-indent-useclass:

buttons.indent.useClass
~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.indent.useClass

   Data type
         string

   Description
         Class name to be used when indenting by means of div sections with
         class attribute.

         Default: indent



.. _buttons-indent-useblockquote:

buttons.indent.useBlockquote
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.indent.useBlockquote

   Data type
         boolean

   Description
         If set, indentation will be produced by means of blockquote tags
         instead of div sections with class attribute.

         Default: 0



.. _buttons-left-useclass:

buttons.left.useClass
~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.left.useClass

   Data type
         string

   Description
         Class name to be used when aligning blocks of text to the left by
         means of class attribute.

         Default: align-left

         Note: This property is also used for text aligment in table
         operations.



.. _buttons-center-useclass:

buttons.center.useClass
~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.center.useClass

   Data type
         string

   Description
         Class name to be used when centering blocks of text by means of class
         attribute.

         Default: align-center

         Note: This property is also used for text aligment in table
         operations.



.. _buttons-right-useclass:

buttons.right.useClass
~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.right.useClass

   Data type
         string

   Description
         Class name to be used when aligning blocks of text to the right by
         means of class attribute.

         Default: align-right

         Note: This property is also used for text aligment in table
         operations.



.. _buttons-justifyfull-useclass:

buttons.justifyfull.useClass
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.justifyfull.useClass

   Data type
         string

   Description
         Class name to be used when justifying blocks of text to both left and
         right by means of class attribute.

         Default: align-justify

         Note: This property is also used for text aligment in table
         operations.



.. _buttons-left-usealignattribute:

buttons.left.useAlignAttribute
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.left.useAlignAttribute

         buttons.center.useAlignAttribute

         buttons.right.useAlignAttribute

         buttons.justifyfull.useAlignAttribute

   Data type
         boolean

   Description
         If anyone of these four properties is set, alignment will be produced
         by means of align attributes instead of class attributes.

         Default: 0



.. _buttons-blockstyle-tags-tag-name-allowedclasses:

buttons.blockstyle.tags.[ *tag-name* ].allowedClasses
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.blockstyle.tags.[ *tag-name* ].allowedClasses

   Data type
         list of id-strings

   Description
         Specifies the classes allowed for the block element identified by the
         tag name. Any string in the list may contain wild card characters. The
         wild card character is "\*" and stands for any sequence of characters.

         The classes must also be defined, using the specific tag selector, in
         the CSS file specified by the contentCSS property.

         If the property is empty for any tag, classes associated with the
         given tag in the contentCSS file are used.

         The classes are presented in the drop-down list in alphabetical order
         in the language used by the backend user.



.. _buttons-blockstyle-tags-all-allowedclasses:

buttons.blockstyle.tags.all.allowedClasses
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.blockstyle.tags.all.allowedClasses

   Data type
         list of id-strings

   Description
         Specifies the classes allowed for all block elements, in addition to
         the classes allowed for each specific element (see above).

         The classes must also be defined, without any tag selector, in the CSS
         file specified contentCSS property.

         The classes are presented in the drop-down list in alphabetical order
         in the language used by the backend user.



.. _buttons-blockstyle-showtagfreeclasses:

buttons.blockstyle.showTagFreeClasses
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.blockstyle.showTagFreeClasses

   Data type
         boolean

   Description
         Specifies that classes not associated with any tag in the contentCSS
         style sheet should be or should not be shown in the block style drop-
         down list.

         Default: 0



.. _buttons-blockstyle-prefixlabelwithclassname:

buttons.blockstyle.prefixLabelWithClassName
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.blockstyle.prefixLabelWithClassName

   Data type
         boolean

   Description
         If set, the option name in the block style drop-down list is prefixed
         with the class name.

         Default: 0



.. _buttons-blockstyle-postfixlabelwithclassname:

buttons.blockstyle.postfixLabelWithClassName
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.blockstyle.postfixLabelWithClassName

   Data type
         boolean

   Description
         If set, the option name e in the block style drop-down list is
         postfixed with the class name.

         Default: 0



.. _buttons-blocktstyle-disablestyleonoptionlabel:

buttons.blocktstyle.disableStyleOnOptionLabel
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.blocktstyle.disableStyleOnOptionLabel

   Data type
         boolean

   Description
         If set, the styling is removed on the options block styling drop-down
         list.

         Default: 0

         See value property of RTE.classes array.



.. _buttons-formattext-orderitems:

buttons.formattext.orderItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formattext.orderItems

   Data type
         list of id-strings

   Description
         Specifies the order in which the options, or inline element types, are
         presented in the text formating drop-down list.

         If not set, the default order will be alphabetical, in the language of
         the current backend user.



.. _buttons-formattext-removeitems:

buttons.formattext.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formattext.removeItems

   Data type
         list of id-strings

   Description
         List of options to be removed from the text formating drop-down list
         using same names as toolbar elements.



.. _buttons-formattext-prefixlabelwithtag:

buttons.formattext.prefixLabelWithTag
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formattext.prefixLabelWithTag

   Data type
         boolean

   Description
         If set, the option name in the text formating drop-down list is
         prefixed with the tagname.

         Default: 0



.. _buttons-formattext-postfixlabelwithtag:

buttons.formattext.postfixLabelWithTag
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.formattext.postfixLabelWithTag

   Data type
         boolean

   Description
         If set, the option name e in the text formating drop-down list is
         postfixed with the tagname.

         Default: 0



.. _buttons-textstyle-tags-tag-name-allowedclasses:

buttons.textstyle.tags.[ *tag-name* ].allowedClasses
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.textstyle.tags.[ *tag-name* ].allowedClasses

   Data type
         list of id-strings

   Description
         Specifies the classes allowed for each inline element (tag) in the
         text styling drop-down list. Any string in the list may contain wild
         card characters. The wild card character is "\*" and stands for any
         sequence of characters.

         Supported tags are: abbr, acronym, b, bdo, big, cite, code, del, dfn,
         em, i, ins, kbd, q, samp, small, span, strike, strong, sub, sup, tt,
         u, var

         The classes must also be defined in the CSS file specified by
         contentCSS property.

         If the property is empty for any tag, classes associated with the
         given tag in the contentCSS file are used.

         The classes are presented in the textstyle drop-down list in
         alphabetical order in the language used by the backend user.



.. _buttons-textstyle-tags-all-allowedclasses:

buttons.textstyle.tags.all.allowedClasses
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.textstyle.tags.all.allowedClasses

   Data type
         list of id-strings

   Description
         Specifies the classes allowed for all inline elements, in addition to
         the classes allowed for each specific element (see above).

         The classes must also be defined in the CSS file specified by
         contentCSS property.

         The classes are presented in the drop-down list in alphabetical order
         in the language used by the backend user.



.. _buttons-textstyle-showtagfreeclasses:

buttons.textstyle.showTagFreeClasses
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.textstyle.showTagFreeClasses

   Data type
         boolean

   Description
         Specifies that classes not associated with any tag in the contentCSS
         style sheet should be or should not be shown in the text styling drop-
         down list.

         Default: 0



.. _buttons-textstyle-prefixlabelwithclassname:

buttons.textstyle.prefixLabelWithClassName
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.textstyle.prefixLabelWithClassName

   Data type
         boolean

   Description
         If set, the option name in the text styling drop-down list is prefixed
         with the class name.

         Default: 0



.. _buttons-textstyle-postfixlabelwithclassname:

buttons.textstyle.postfixLabelWithClassName
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.textstyle.postfixLabelWithClassName

   Data type
         boolean

   Description
         If set, the option name e in the text styling drop-down list is
         postfixed with the class name.

         Default: 0



.. _buttons-textstyle-disablestyleonoptionlabel:

buttons.textstyle.disableStyleOnOptionLabel
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.textstyle.disableStyleOnOptionLabel

   Data type
         boolean

   Description
         If set, the styling is removed on the options text styling drop-down
         list.

         Default: 0

         See value property of RTE.classes array.



.. _buttons-language-restricttoitems:

buttons.language.restrictToItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.language.restrictToItems

   Data type
         list of strings

   Description
         List of language ISO codes to which the language marking drop-down
         list is limited to.

         Note: If not set, all languages found in the static\_languages table
         will appear in the drop-down list.



.. _buttons-language-uselangattribute:

buttons.language.useLangAttribute
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.language.useLangAttribute

   Data type
         boolean

   Description
         If set, the lang attribute is used fro language marks.

         Default: 1

         Note: If both useLangAttribute and useXmlLangAttribute are unset, the
         lang attribute will be used.



.. _buttons-language-usexmllangattribute:

buttons.language.useXmlLangAttribute
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.language.useXmlLangAttribute

   Data type
         boolean

   Description
         If set, the xml:lang attribute is used fro language marks.

         Default: 0



.. _buttons-language-prefixlabelwithcode:

buttons.language.prefixLabelWithCode
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.language.prefixLabelWithCode

   Data type
         boolean

   Description
         If set, the option name e in the language marking drop-down list is
         prefixed with the language ISO code.

         Default: 0



.. _buttons-language-postfixlabelwithcode:

buttons.language.postfixLabelWithCode
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.language.postfixLabelWithCode

   Data type
         boolean

   Description
         If set, the option name e in the language marking drop-down list is
         postfixed with the language ISO code.

         Default: 0



.. _buttons-spellcheck-enablepersonaldictionaries:

buttons.spellcheck.enablePersonalDictionaries
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.spellcheck.enablePersonalDictionaries

   Data type
         boolean

   Description
         If set, personal dictionaries are enabled.

         Default: 0

         Note: The feature must also be enabled in User TSconfig.

         Note: Personal dictionaries are stored in subdirectories of
         uploads/tx\_rtehtmlarea



.. _buttons-spellcheck-dictionaries-restricttoitems:

buttons.spellcheck.dictionaries.restrictToItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.spellcheck.dictionaries.restrictToItems

   Data type
         list-of-id-strings

   Description
         List of Aspell dictionary codes to which the drop-down list of
         dictionaries is limited in the spell checker dialogue.

         Note: If not set, all dictionaries obtained from Aspell will appear in
         the drop-down list.



.. _buttons-spellcheck-dictionaries-language-iso-code-defaultvalue:

buttons.spellcheck.dictionaries.[ *language-iso-code* ].defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.spellcheck.dictionaries.[ *language-iso-code* ].defaultValue

   Data type
         string

   Description
         Aspell dictionary code of the dictionary to be used by default to
         spell check a content element in the language specified by the ISO
         code. The specified dictionary will be pre-selected in the drop-down
         list of dictionaries.

         Default: the language ISO code.



.. _buttons-image-typo3browser-disabled:

buttons.image.TYPO3Browser.disabled
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.image.TYPO3Browser.disabled

   Data type
         boolean

   Description
         If set, the TYPO3 image browser is disabled.

         Default: 0

         Note: The TYPO3 image browser is never available when the editor is
         used in the frontend.



.. _buttons-image-options-imageHandler:

buttons.image.options.imageHandler
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.image.options.imageHandler

   Data type
         array

   Description
         Configuration of available image handlers.

         Extension developers may add their own handler by adding their own
         entry to this array.
         The syntax is:

         .. code:: ts
            <handler-id> {
               handler = Vendor\Ext\YourHandlerClass::class
               label = LLL:EXT:ext/Resources/Private/Language/locallang.xlf:the_label
               displayAfter = image
               scanAfter = image
            }

         For a detailed description of the options, please refer to the link handler documentation.



.. _buttons-image-options-magic-maxwidth:

buttons.image.options.magic.maxWidth
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.image.options.magic.maxWidth

   Data type
         int+

   Description
         Maximum width of a magic image in pixels at the time of its initial
         insertion.

         Default: 300

         Note: The width of the magic image may be made larger when updating
         the image properties. However, the image is not recreated, only its
         HTML width attribute is updated.



.. _buttons-image-options-magic-maxheight:

buttons.image.options.magic.maxHeight
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.image.options.magic.maxHeight

   Data type
         int+

   Description
         Maximum height of a magic image in pixels at the time of its initial
         insertion.

         Default: 1000

         Note: By setting a large enough height, images should be resized based
         on their width.

         Note: The height of the magic image may be made larger when updating
         the image properties. However, the image is not recreated, only its
         HTML height attribute is updated.



.. _buttons-image-options-plain-maxwidth:

buttons.image.options.plain.maxWidth
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.image.options.plain.maxWidth

   Data type
         int+

   Description
         Maximum width of selectable plain images in pixels.

         Default: 640



.. _buttons-image-options-plain-maxheight:

buttons.image.options.plain.maxHeight
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.image.options.plain.maxHeight

   Data type
         int+

   Description
         Maximum height of selectable plain images in pixels.

         Default: 680



.. _buttons-image-properties-removeitems:

buttons.image.properties.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.image.properties.removeItems

   Data type
         list of strings

   Description
         List of properties to remove from the image properties editing window.
         Key list is align, alt, border, class, clickenlarge, float, height,
         paddingTop, paddingRight, paddingBottom, paddingLeft, title, width

         Note: When a plain image is edited, if proc.plainImageMode is set to
         lockDimentions, lockRatio or lockRatioWhenSmaller, the height property
         is removed from the properties window. If proc.plainImageMode is set
         to lockDimensions, both the width and height properties are removed.



.. _buttons-image-properties-class-allowedclasses:

buttons.image.properties.class.allowedClasses
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.image.properties.class.allowedClasses

   Data type
         list of id-strings

   Description
         Classes available in the Insert/Modify image dialogue.

         Each of the listed classes must be defined in the CSS file specified
         by the contentCSS property.



.. _buttons-image-properties-class-default:

buttons.image.properties.class.default
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.image.properties.class.default

   Data type
         string

   Description
         Class to be assigned by default to an image when it is inserted in the
         RTE.



.. _buttons-link-typo3browser-disabled:

buttons.link.TYPO3Browser.disabled
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.TYPO3Browser.disabled

   Data type
         boolean

   Description
         If set, the TYPO3 element browser is disabled.

         Default: 0

         Note: The TYPO3 element browser is never available when the editor is
         used in the frontend.



.. _buttons-link-options-removeitems:

buttons.link.options.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.options.removeItems

   Data type
         list of strings

   Description
         List of tab items to remove from the dialog of the link button.
         Possible tab items are: page,file,url,mail

         Note: More tabs may be provided by extensions.



.. _buttons-link-targetselector-disabled:

buttons.link.targetSelector.disabled
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.targetSelector.disabled

   Data type
         boolean

   Description
         If set, the selection of link target is removed from the link
         insertion/update dialog.

         Default : 0



.. _buttons-link-pageidselector-enabled:

buttons.link.pageIdSelector.enabled
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.pageIdSelector.enabled

   Data type
         boolean

   Description
         If set, the specification of a page id, without using the page tree,
         is enabled in the link insertion/update dialog.

         Note: This feature is intended for authors who have to deal with a
         very large page tree. Note that the feature is disabled by default.

         Default: 0



.. _buttons-link-queryparametersselector-enabled:

buttons.link.queryParametersSelector.enabled
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.queryParametersSelector.enabled

   Data type
         boolean

   Description
         If set, an additional field is enabbled in the link insertion/update
         dialogue allowing authors to specify query parameters to be added on
         the link

         Default: 0



.. _buttons-link-relattribute-enabled:

buttons.link.relAttribute.enabled
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.relAttribute.enabled

   Data type
         boolean

   Description
         If set, an additional field is enabled in the link insertion/update
         dialogue allowing authors to specify a rel attribute to be added to
         the link.

         Default: 0



.. _buttons-link-properties-class-allowedclasses:

buttons.link.properties.class.allowedClasses
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.properties.class.allowedClasses

   Data type
         list of id-strings

   Description
         Classes available in the Insert/Modify link dialogue.

         These classes may be defined by the RTE.classesAnchor property.



.. _buttons-link-properties-class-default:

buttons.link.properties.class.default
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.properties.class.default

   Data type
         string

   Description
         Class to be assigned by default to a link when it is inserted in the
         RTE. See also buttons.link.[ *type* ].properties.class.default.



.. _buttons-link-type-properties-class-default:

buttons.link.[ *type* ].properties.class.default
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.[ *type* ].properties.class.default

   Data type
         string

   Description
         The name of the default class selector for links of the given type.
         Possible types are: page, file, url, mail, spec. More types may be
         provided by extensions such as DAM.



.. _buttons-link-properties-class-required:

buttons.link.properties.class.required
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.properties.class.required

   Data type
         boolean

   Description
         If set, a class must be selected for any link. Therefore, the empty
         option is removed from the class selector.



.. _buttons-link-type-properties-class-required:

buttons.link.[ *type* ].properties.class.required
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.[ *type* ].properties.class.required

   Data type
         boolean

   Description
         If set, a class must be selected for any link of the given type.
         Therefore, the empty option is removed from the class selector.
         Possible types are: page, file, url, mail, spec. More types may be
         provided by extensions such as DAM.



.. _buttons-link-properties-title-readonly:

buttons.link.properties.title.readOnly
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.properties.title.readOnly

   Data type
         boolean

   Description
         If set, the title is set based on the RTE.classesAnchor configuration
         and cannot be modified by the author.



.. _buttons-link-type-properties-title-readonly:

buttons.link.[ *type* ].properties.title.readOnly
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.[ *type* ].properties.title.readOnly

   Data type
         boolean

   Description
         If set, the title for the given type of link is set based on the
         RTE.classesAnchor configuration and cannot be modified by the author.
         Possible types are: page, file, url, mail, spec. More types may be
         provided by extensions such as DAM.



.. _buttons-link-properties-target-default:

buttons.link.properties.target.default
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.properties.target.default

   Data type
         string

   Description
         This sets the default target for new links in the RTE.

         Note: See also the classesAnchor configuration.



.. _buttons-link-type-properties-target-default:

buttons.link.[ *type* ].properties.target.default
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.link.[ *type* ].properties.target.default

   Data type
         string

   Description
         Specifies a default target for links of the given type.
         Possible types are: page, file, url, mail, spec. More types may be
         provided by extensions.

         Note: See also the classesAnchor configuration.



.. _buttons-abbreviation-pages:

buttons.abbreviation.pages
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.abbreviation.pages

   Data type
         list of page id's

   Description
         List of page id's from which to obtain the abbreviation records.

         Note: If not set, the list of current webmounts is used.

         Note: In IE, before IE7, the abreviation tab of the abbreviation dialogue
         is never shown.



.. _buttons-abbreviation-recursive:

buttons.abbreviation.recursive
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.abbreviation.recursive

   Data type
         int

   Description
         The number of levels in the page tree, under each page listed in
         buttons.abbreviation.pages or under each webmount, from which abbreviations
         are retrieved.

         Default: 0



.. _buttons-abbreviation-lockbeusertodbmounts:

buttons.abbreviation.lockBeUserToDBmounts
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.abbreviation.lockBeUserToDBmounts

   Data type
         boolean

   Description
         If set, the pid's listed under buttons.abbreviation.pages (see above) are
         validated against the user's current webmounts.

         If not set or if the user is admin, buttons.abbreviation.pages is ignored
         and abbreviations from all pages are retrieved.

         Default: The default value of this property is the value of the
         property with same name in the backend section of theTYPO3
         configuration as set by the Install Tool.



.. _buttons-abbreviation-removefieldsets:

buttons.abbreviation.removeFieldsets
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.abbreviation.removeFieldsets

   Data type
         list of strings

   Description
         List of fieldsets to remove from the abbreviation dialogue.

         Possible string values are: acronym, definedAcronym, abbreviation,
         definedAbbreviation



.. _buttons-acronym-pages:

buttons.acronym.pages
~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         This property is deprecated. Use :ref:`buttons.abbreviation.pages <buttons-abbreviation-pages>`



.. _buttons-acronym-recursive:

buttons.acronym.recursive
~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         This property is deprecated. Use :ref:`buttons.abbreviation.recursive <buttons-abbreviation-recursive>`



.. _buttons-acronym-lockbeusertodbmounts:

buttons.acronym.lockBeUserToDBmounts
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         This property is deprecated. Use :ref:`buttons.abbreviation.lockBeUserToDBmounts <buttons-abbreviation-lockBeUserToDBmounts>`



.. _colors:

colors
~~~~~~

.. container:: table-row

   Property
         colors

   Data type
         list of id-strings

   Description
         Defines the specific colors generally available in the color
         selectors. The id-strings must be configured in the RTE.colors array
         (see description earlier).

         **Example:** ::

            RTE.default {
              colors = color1, color2,noColor
            }



.. _disablecolorpicker:

disableColorPicker
~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         disableColorPicker

   Data type
         boolean

   Description
         Disables the color picker matrix in all color dialogs. The color
         picker lets you select web-colors.



.. _buttons-fontstyle-removeitems:

buttons.fontstyle.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.fontstyle.removeItems

   Data type
         list of id-numbers,

         \* removes all

   Description
         Lets you remove any of the default font faces in the Font Style
         selector. Values are ranging from 1 to 9. These are the possible
         options, and their respective name => value pairs, that you can
         remove:

         1: Arial => Arial,sans-serif

         2: Arial Black => 'Arial Black',sans-serif

         3: Verdana => Verdana,Arial,sans-serif

         4: 'Times New Roman' => 'Times New Roman',Times,serif

         5: Garamond => Garamond

         6: Lucida Handwriting => Lucida Handwriting

         7: Courier => Courier

         8: Webdings => Webdings

         9: Wingdings => Wingdings



.. _buttons-fontstyle-additems:

buttons.fontstyle.addItems
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.fontstyle.addItems

   Data type
         list of id-strings

   Description
         Defines additional fonts available in the font selector. The id-
         strings must be configured in the RTE.fonts array (see description
         earlier).



.. _buttons-fontstyle-defaultitem:

buttons.fontstyle.defaultItem
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.fontstyle.defaultItem

   Data type
         string

   Description
         Specifies the name of the default font style. The name is the name of
         one of the default font faces, or the name associated to one of fonts
         configured in the RTE.fonts array (see description earlier).

         Note: The value associated to the default font style should be exactly
         the same as the value of the default font-family property specified in
         the site style sheet as referred to by property
         RTE.default.contentCSS.



.. _buttons-fontsize-removeitems:

buttons.fontsize.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.fontsize.removeItems

   Data type
         list of size-numbers,

         \* removes all

   Description
         Lets you disable any of the default font sizes available in the Font
         Size selector. Values are ranging from 1 to 7. These are the possible
         options, and their respective name => value pairs, that you can
         remove:

         1: Extra small => 8px

         2: Very small => 9px

         3: Small => 10px

         4: Medium => 12px

         5: Large => 16px

         6: Very large => 24px

         7: Extra large => 32px



.. _buttons-fontsize-additems:

buttons.fontsize.addItems
~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.fontsize.addItems

   Data type
         list of id-strings

   Description
         Defines additional font sizes available in the font size selector. The
         id-strings must be configured in the RTE.fontSizes array (see
         description earlier).



.. _buttons-fontsize-defaultitem:

buttons.fontsize.defaultItem
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.fontsize.defaultItem

   Data type
         string

   Description
         Specifies the name of the default font size. The name is the name of
         one of the default font sizes, or the name associated to one of font
         sizes configured in the RTE.fontSizes array (see description earlier).

         Note: The value associated to the default font size should be exactly
         the same as the value of the default font-size property specified in
         the site style sheet as referred to by property
         RTE.default.contentCSS. For correct behaviour in non-IE browsers, the
         value of the default font size should be specified in px units.



.. _hidetableoperationsintoolbar:

hideTableOperationsInToolbar
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         hideTableOperationsInToolbar

   Data type
         boolean

   Description
         Specifies that table operations buttons should be hidden in the tool
         bar or not.

         Default: 0

         Note: If enabled, table operations will appear only in the context
         menu, provided that they may be enabled in the given context.



.. _buttons-toggleborders-keepintoolbar:

buttons.toggleborders.keepInToolbar
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.toggleborders.keepInToolbar

   Data type
         boolean

   Description
         If set, the toggleborders button will be kept in the tool bar even if
         property hideTableOperationsInToolbar is set.

         Default: 0



.. _buttons-toggleborders-setontablecreation:

buttons.toggleborders.setOnTableCreation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.toggleborders.setOnTableCreation

   Data type
         boolean

   Description
         If set, and if the toggleborders button is enabled, the table borders
         will be toggled on when a new table is created.

         Default : 0



.. _buttons-toggleborders-setonrteopen:

buttons.toggleborders.setOnRTEOpen
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.toggleborders.setOnRTEOpen

   Data type
         boolean

   Description
         If set, and if the toggleborders button is enabled, the table borders
         will be toggled on when the RTE opens.

         Default : 0



.. _buttons-button-name-hotkey:

buttons.[ *button-name* ].hotKey
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.[ *button-name* ].hotKey

   Data type
         character

   Description
         A hotkey will be associated with the specified button-name.

         Note: Care should be taken that the hotkey does not conflict with pre-
         defined hotkeys. If it does, the hotkey will override any previously
         registered hotkey.



.. _buttons-button-name-width:

buttons.[ *button-name* ].width
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.[ *button-name* ].width

   Data type
         int+

   Description
         The width of the field in the toolbar when the button is a dropdown
         list.



.. _buttons-button-name-listwidth:

buttons.[ *button-name* ].listWidth
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.[ *button-name* ].listWidth

   Data type
         int+

   Description
         The width of the dropdown list when the button is a dropdown list.

         Defauls to the width of the field in the toolbar.



.. _buttons-button-name-maxheight:

buttons.[ *button-name* ].maxHeight
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.[ *button-name* ].maxHeight

   Data type
         int+

   Description
         The maximum height of the dropdown list when the button is a dropdown
         list.



.. _buttons-button-name-dialoguewindow-width:

buttons.[ *button-name* ].dialogueWindow.width
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.[ *button-name* ].dialogueWindow.width

   Data type
         int+

   Description
         The opening width of the dialogue window opened when the button is
         pressed.



.. _buttons-button-name-dialoguewindow-height:

buttons.[ *button-name* ].dialogueWindow.height
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.[ *button-name* ].dialogueWindow.height

   Data type
         int+

   Description
         The opening height of the dialogue window opened when the button is
         pressed.



.. _buttons-button-name-dialoguewindow-positionfromtop:

buttons.[ *button-name* ].dialogueWindow.positionFromTop
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.[ *button-name* ].dialogueWindow.positionFromTop

   Data type
         int+

   Description
         The opening position from the top of the screen of the dialogue window
         opened when the button is pressed.



.. _buttons-button-name-dialoguewindow-positionfromleft:

buttons.[ *button-name* ].dialogueWindow.positionFromLeft
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.[ *button-name* ].dialogueWindow.positionFromLeft

   Data type
         int+

   Description
         The opening position from the left of the screen of the dialogue
         window opened when the button is pressed.



.. _buttons-button-name-dialoguewindow-donotresize:

buttons.[ *button-name* ].dialogueWindow.doNotResize
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.[ *button-name* ].dialogueWindow.doNotResize

   Data type
         boolean

   Description
         If set, the window that is opened when the button is pressed will not
         be resized to its contents.

         Default: 0



.. _buttons-button-name-dialoguewindow-donotcenter:

buttons.[ *button-name* ].dialogueWindow.doNotCenter
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.[ *button-name* ].dialogueWindow.doNotCenter

   Data type
         boolean

   Description
         If set, the window that is opened when the button is pressed will not
         be centered in the parent window.

         Default: 0



.. _skin:

skin
~~~~

.. container:: table-row

   Property
         skin

   Data type
         resource

   Description
         The skin contains the CSS files and the images used to style the
         editor.

         The skin is specified by specifying the location of the main CSS file
         to be used to style the editor. The folder containing the CSS file
         MUST also contain a structure of folders and files identical to the
         structure found in the folder of the default skin. All folder names
         and all file names must be identical.

         Default: EXT:rtehtmlarea/htmlarea/skins/default/htmlarea.css

         Note: these example skins do not work in Mozilla 1.3; if the property
         is set to one of them, the default skin will be used when the browser
         is Mozilla 1.3.



.. _contentcss:

contentCSS
~~~~~~~~~~

.. container:: table-row

   Property
         contentCSS
         contentCSS.[id-string]

   Data type
         resource(s)

   Description
         The CSS file that contains the style definitions that should be
         applied to the edited contents.

         The selectors defined in this file will also be used in the block
         style and text style selection lists.

         Default: EXT:rtehtmlarea/res/contentcss/default.css

         For example, this default could be overridden with:
         fileadmin/styles/my\_contentCSS.css

         Multiple files may be specified by using contentCSS.[id-string].
         For example::

            contentCSS {
                file1 = fileadmin/myStylesheet1.css
                file2 = fileadmin/myStylesheet2.css
            }



.. _proc:

proc
~~~~

.. container:: table-row

   Property
         proc

   Data type
         ->PROC

   Description
         Customization of the server processing of the content - also called
         'transformations'.

         See :ref:`t3api:transformations`



.. _enablewordclean:

enableWordClean
~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         enableWordClean

   Data type
         boolean

   Description
         Specifies that text pasted from external sources, presumably from
         Microsoft Word, should be "cleaned" or not.

         Default: 0

         Note:If no HTMLparser configuration is specified, a limited default
         cleaning operation will be performed. If a HTMLparser specification is
         specified, parsing will be performed on the server at the time of the
         paste operation.

         Note: Additional cleanup may be performed by the user when the
         removeformat button is enabled.

         Note: Cleaning on paste cannot be performed in Opera.

         Note: The same cleaning operation is performed with hotkey CTRL+0,
         including in Opera.



.. _enablewordclean_HTMLparser:

enableWordClean.HTMLparser
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         enableWordClean.HTMLparser

   Data type
         boolean/

         ->HTMLparser

   Description
         HTMLparser specification use by the enableWordClean feature.

         Default: 0

         Note:If no HTMLparser configuration is specified, a limited default
         cleaning operation will be performed. If a HTMLparser specification is
         specified, parsing will be performed on the server at the time of the
         paste operation.

         Note: If an HTMLparser configuration is specified, care should be
         taken that span tags with id attribute are not removed by the cleaning
         operation. If they are removed, the cursor position will not be
         restored in non-IE browsers after the paste operation, and the cursor
         will then be positionned at the start of the text.



.. _enablewordclean-hotkey:

enableWordClean.hotKey
~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         enableWordClean.hotKey

   Data type
         character

   Description
         The default hotkey of the cleaning operation, CTRL+0, is replaced by
         CTRL+the specified character.



.. _removecomments:

removeComments
~~~~~~~~~~~~~~

.. container:: table-row

   Property
         removeComments

   Data type
         boolean

   Description
         Specifies that html comments should be removed or not by the editor on
         save and on toggle to HTML source mode.

         Default: 0



.. _removetags:

removeTags
~~~~~~~~~~

.. container:: table-row

   Property
         removeTags

   Data type
         list of tags

   Description
         List of tags that should be removed by the editor on save and on
         toggle to HTML source mode.



.. _removetagsandcontents:

removeTagsAndContents
~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         removeTagsAndContents

   Data type
         list of tags

   Description
         List of tags that should be removed by the editor, contents included,
         on save and on toggle to HTML source mode. The tags and the contents
         inside the tags will be removed.



.. _customtags:

customTags
~~~~~~~~~~

.. container:: table-row

   Property
         customTags

   Data type
         list of tags

   Description
         List of custom tags that may appear in content.

         Note: When IE is used with standards mode older than IE9, custom tags
         are not correctly handle. This list of custom tags is then used to let
         them be known to IE so that they are correctly handled by this
         browser.



.. _usecss:

useCSS
~~~~~~

.. container:: table-row

   Property
         useCSS

   Data type
         boolean

   Description
         Specifies that Mozilla/Firefox should use style attributes or not.
         When enabled, Mozilla/Firefox use span tags with style attributes
         rather than tags such as b, i, font, etc.

         Default: 0



.. _disableenterparagraphs:

disableEnterParagraphs
~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         disableEnterParagraphs

   Data type
         boolean

   Description
         Specifies that the insertion of paragraphs when hitting the Enter key
         in Mozilla/Firefox and Safari should be disabled.

         Default: 0

         Note: If NOT enabled, the behavior of Mozilla/Firefox and Safari is
         modified as follows: when the Enter key is pressed, instead of
         inserting a br tag, the behavior of Internet Explorer is simulated and
         a new paragraph is created.

         Note: If enabled, the behavior of Mozilla/Firefox and Safari is not
         modified: a br tag is inserted when the Enter key is pressed.



.. _disableobjectresizing:

disableObjectResizing
~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         disableObjectResizing

   Data type
         boolean

   Description
         Specifies that Mozilla/Firefox should not provide handles for resizing
         objects such as images and tables.

         Default: 0



.. _removetrailingbr:

removeTrailingBR
~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         removeTrailingBR

   Data type
         boolean

   Description
         Specifies that trailing br tags should be removed from block elements.

         Default: 0

         Note: If set, any trailing br tag in a block element will be removed
         on save and/or change mode. However, multiple trailing br tags will be
         preserved.

         Note: In Mozilla/Firefox/Netscape, whenever some text is entered in an
         empty block, a trailing br tag is added by the browser.



.. _buttons-inserttag-denytags:

buttons.inserttag.denyTags
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.inserttag.denyTags

   Data type
         list of tags

   Description
         List of tag names that should NOT be shown by the dialog of the
         inserttag button.

         Note: Listed tag names should be among the following: a, abbr,
         acronym, address, b, big, blockquote, cite, code, div, em, fieldset,
         font, h1, h2, h3, h4, h5, h6, i, legend, li, ol, p, pre, q, small,
         span, strong, sub, sup, table, tt, ul



.. _buttons-inserttag-allowedattribs:

buttons.inserttag.allowedAttribs
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.inserttag.allowedAttribs

   Data type
         list of attributes

   Description
         List of attribute names that should be shown for all tags in the
         dialog of the inserttag button.

         Note: Listed attribute names should be among the following: class,
         dir, id, lang, onFocus, onBlur, onClick, onDblClick, onMouseDown,
         onMouseUp, onMouseOver, onMouseMove, onMouseOut, onKeyPress,
         onKeyDown, onKeyUp, style, title, xml:lang



.. _buttons-inserttag-tags-tagname-allowedattribs:

buttons.inserttag.tags. *[tagname]* .allowedAttribs
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.inserttag.tags. *[tagname]* .allowedAttribs

   Data type
         list of attributes

   Description
         List of attribute names that should be shown for the specified
         *tagname* in the dialog of the inserttag button, in addition to the
         attribute names specified by property
         buttons.inserttag.allowedAttribs.



.. _buttons-table-disableenterparagraphs:

buttons.table.disableEnterParagraphs
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.disableEnterParagraphs

   Data type
         boolean

   Description
         If set, this property will prevent the insertion of paragraphs in
         table cells when the enter key is pressed.

         Default: 0



.. _buttons-table-enablehandles:

buttons.table.enableHandles
~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.enableHandles

   Data type
         boolean

   Description
         If set, table handles will be enabled in Firefox. These Firefox-
         specific handles allow to delete/insert rows and columns using small
         handles displayed on table borders. However, insert operations also
         add a style attribute on inserted cells.

         Default: 0



.. _disablealignmentfieldsetintableoperations:

disableAlignmentFieldsetInTableOperations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         disableAlignmentFieldsetInTableOperations

         disableSpacingFieldsetInTableOperations

         disableColorFieldsetInTableOperations

         disableLayoutFieldsetInTableOperations

         disableBordersFieldsetInTableOperations

   Data type
         boolean

   Description
         Disables the corresponding fieldset in all table operations dialogues.

         Default: 0



.. _buttons-table-removefieldsets:

buttons.table.removeFieldsets
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.removeFieldsets

   Data type
         list of strings

   Description
         List of fieldsets to remove from the table creation dialogue. Key list
         is alignment, borders, color, description, language, layout, spacing,
         style



.. _buttons-tableproperties-removefieldsets:

buttons.tableproperties.removeFieldsets
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.tableproperties.removeFieldsets

   Data type
         list of strings

   Description
         List of fieldsets to remove from the table properties edition
         dialogue. Key list is alignment, borders, color, description,
         language, layout, spacing, style



.. _buttons-table-properties-required:

buttons.table.properties.required
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.required

   Data type
         list of strings

   Description
         List of fields for which a value is required in the table creation and
         table properties edition dialogues. Possible values are: caption,
         summary, captionOrSummary



.. _buttons-table-properties-removed:

buttons.table.properties.removed
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.removed

   Data type
         list of strings

   Description
         List of fields to remove from the table creation and table properties
         edition dialogues. Possible values are: width, height, float, headers,
         language, direction



.. _buttons-table-properties-numberofrows-defaultvalue:

buttons.table.properties.numberOfRows.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.numberOfRows.defaultValue

   Data type
         int+

   Description
         Default value for the number of rows to include in a table on
         creation.

         Default: 2



.. _buttons-table-properties-numberofcolumns-defaultvalue:

buttons.table.properties.numberOfColumns.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.numberOfColumns.defaultValue

   Data type
         int+

   Description
         Default value for the number of columns to include in a table on
         creation.

         Default: 4



.. _buttons-table-properties-headers-defaultvalue:

buttons.table.properties.headers.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.headers.defaultValue

   Data type
         string

   Description
         Default selected option in the headers layout selector in the table
         creation dialogue. Possible values are: none, top, left, both

         Default: top



.. _buttons-table-properties-headers-removeitems:

buttons.table.properties.headers.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.headers.removeItems

   Data type
         list of strings

   Description
         List of items to remove from the headers layout selector in the table
         creation dialogue. Key list is: none, top, left, both

         Default: void



.. _buttons-table-properties-headers-both-useheaderclass:

buttons.table.properties.headers.both.useHeaderClass
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.headers.both.useHeaderClass

   Data type
         list of strings

   Description
         A class to be assigned to the top row when the headers property
         specifies both.

         Default: thead



.. _buttons-table-properties-tableclass-defaultvalue:

buttons.table.properties.tableClass.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.tableClass.defaultValue

   Data type
         string

   Description
         Default selected class in the table class selector in the table
         creation dialogue.

         Default: void



.. _buttons-table-properties-width-defaultvalue:

buttons.table.properties.width.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.width.defaultValue

   Data type
         +int

   Description
         Default value of the table wdth in the table creation dialogue.

         Default: void



.. _buttons-table-properties-widthunit-defaultvalue:

buttons.table.properties.widthUnit.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.widthUnit.defaultValue

   Data type
         string

   Description
         Default selected unit in the width unit selector in the table creation
         dialogue. Possible values are: %, px or em

         Default: %



.. _buttons-table-properties-widthunit-removeitems:

buttons.table.properties.widthUnit.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.widthUnit.removeItems

   Data type
         list of strings

   Description
         List of items to remove from the table width unit selector in the
         table creation dialogue. Key list is: %, px, em

         Default: void



.. _buttons-table-properties-height-defaultvalue:

buttons.table.properties.height.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.height.defaultValue

   Data type
         +int

   Description
         Default value of the table height in the table creation dialogue.

         Default: void



.. _buttons-table-properties-heightunit-defaultvalue:

buttons.table.properties.heightUnit.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.heightUnit.defaultValue

   Data type
         string

   Description
         Default selected unit in the height unit selector in the table
         creation dialogue. Possible values are: %, px or em

         Default: %



.. _buttons-table-properties-heightunit-removeitems:

buttons.table.properties.heightUnit.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.heightUnit.removeItems

   Data type
         list of strings

   Description
         List of items to remove from the table height unit selector in the
         table creation dialogue. Key list is: %, px, em

         Default: void



.. _buttons-table-properties-float-defaultvalue:

buttons.table.properties.float.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.float.defaultValue

   Data type
         string

   Description
         Default selected option in the table float selector in the table
         creation and properties edition dialogues.. Possible values are: not
         set, left, right

         Default: not set



.. _buttons-table-properties-float-left-useclass:

buttons.table.properties.float.left.useClass
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.float.left.useClass

   Data type
         string

   Description
         Class name to be assigned when left is selected in the table float
         selector in the table creation and properties edition dialogues.

         Default: float-left



.. _buttons-table-properties-float-right-useclass:

buttons.table.properties.float.right.useClass
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.float.right.useClass

   Data type
         string

   Description
         Class name to be assigned when right is selected in the table float
         selector in the table creation and properties edition dialogues.

         Default: float-right



.. _buttons-table-properties-float-removeitems:

buttons.table.properties.float.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.float.removeItems

   Data type
         list of strings

   Description
         List of items to remove from the table float selector in the table
         creation and properties edition dialogues. Key list is: not set, left,
         right

         Default: void



.. _buttons-table-properties-cellpadding-defaultvalue:

buttons.table.properties.cellpadding.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.cellpadding.defaultValue

   Data type
         +int

   Description
         Default value of the table cellpadding attribute in the table creation
         and properties edition dialogues

         Default: void



.. _buttons-table-properties-cellspacing-defaultvalue:

buttons.table.properties.cellspacing.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.cellspacing.defaultValue

   Data type
         +int

   Description
         Default value of the table cellspacing attribute in the table creation
         and properties edition dialogues .

         Default: void



.. _buttons-table-properties-borderwidth-defaultvalue:

buttons.table.properties.borderWidth.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.borderWidth.defaultValue

   Data type
         +int

   Description
         Default value of the table border width attribute in the table
         creation and properties edition dialogues

         Default: void



.. _buttons-table-properties-borderstyle-defaultvalue:

buttons.table.properties.borderStyle.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.borderStyle.defaultValue

   Data type
         string

   Description
         Default selected style in the border style selector in the table
         creation dialogue. Possible values are: not set, none, dotted, dashed,
         solid, double, groove, ridge, inset, outset

         Default: not set



.. _buttons-table-properties-borderstyle-removeitems:

buttons.table.properties.borderStyle.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.table.properties.borderStyle.removeItems

   Data type
         list of strings

   Description
         List of items to remove from the table border style selector in the
         table creation dialogue. Key list is: not set, none, dotted, dashed,
         solid, double, groove, ridge, inset, outset

         Default: void



.. _buttons-rowproperties-removefieldsets:

buttons.rowproperties.removeFieldsets
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.rowproperties.removeFieldsets

   Data type
         list of strings

   Description
         List of fieldsets to remove from the table row properties edition
         dialogue. Key list is alignment, borders, color, language, layout,
         rowgroup, style

         Default: void



.. _buttons-rowproperties-properties-removed:

buttons.rowproperties.properties.removed
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.rowproperties.properties.removed

   Data type
         list of strings

   Description
         List of fields to remove from the table row properties edition
         dialogue. Possible values are: width, height, language, direction

         Default: void



.. _buttons-rowproperties-properties-width-defaultvalue:

buttons.rowproperties.properties.width.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.rowproperties.properties.width.defaultValue

   Data type
         +int

   Description
         Default value of the row wdth in the table row properties edition
         dialogue.

         Default: void



.. _buttons-rowproperties-properties-widthunit-defaultvalue:

buttons.rowproperties.properties.widthUnit.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.rowproperties.properties.widthUnit.defaultValue

   Data type
         string

   Description
         Default selected unit in the row width unit selector in the table row
         properties edition dialogue. Possible values are: %, px or em

         Default: %



.. _buttons-rowproperties-properties-widthunit-removeitems:

buttons.rowproperties.properties.widthUnit.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.rowproperties.properties.widthUnit.removeItems

   Data type
         list of strings

   Description
         List of items to remove from the row width unit selector in the table
         row properties edition dialogue. Key list is: %, px, em

         Default: void



.. _buttons-rowproperties-properties-height-defaultvalue:

buttons.rowproperties.properties.height.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.rowproperties.properties.height.defaultValue

   Data type
         +int

   Description
         Default value of the row height in the table row properties edition
         dialogue.

         Default: void



.. _buttons-rowproperties-properties-heightunit-defaultvalue:

buttons.rowproperties.properties.heightUnit.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.rowproperties.properties.heightUnit.defaultValue

   Data type
         string

   Description
         Default selected unit in the row height unit selector iin the table
         row properties edition dialogue. Possible values are: %, px or em

         Default: %



.. _buttons-rowproperties-properties-heightunit-removeitems:

buttons.rowproperties.properties.heightUnit.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.rowproperties.properties.heightUnit.removeItems

   Data type
         list of strings

   Description
         List of items to remove from the row height unit selector in the table
         row properties edition dialogue. Key list is: %, px, em

         Default: void



.. _buttons-rowproperties-properties-borderstyle-removeitems:

buttons.rowproperties.properties.borderStyle.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.rowproperties.properties.borderStyle.removeItems

   Data type
         list of strings

   Description
         List of items to remove from the row border style selector in the
         table row properties edition dialogue. Key list is: not set, none,
         dotted, dashed, solid, double, groove, ridge, inset, outset

         Default: void



.. _buttons-columnproperties-removefieldsets:

buttons.columnproperties.removeFieldsets
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.columnproperties.removeFieldsets

   Data type
         list of strings

   Description
         List of fieldsets to remove from the column cells properties edition
         dialogue. Key list is alignment, borders, color, language, layout,
         style



.. _buttons-cellproperties-removefieldsets:

buttons.cellproperties.removeFieldsets
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.cellproperties.removeFieldsets

   Data type
         list of strings

   Description
         List of fieldsets to remove from the cell properties edition dialogue.
         Key list is alignment, borders, color, language, layout, style



.. _buttons-cellproperties-properties-removed:

buttons.cellproperties.properties.removed
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.cellproperties.properties.removed

   Data type
         list of strings

   Description
         List of fields to remove from the cell properties and column cells
         properties edition dialogues. Possible values are: width, height,
         language, direction

         Default: void



.. _buttons-cellproperties-properties-width-defaultvalue:

buttons.cellproperties.properties.width.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.cellproperties.properties.width.defaultValue

   Data type
         +int

   Description
         Default value of the row wdth in the cell properties and column cells
         properties edition dialogues.

         Default: void



.. _buttons-cellproperties-properties-widthunit-defaultvalue:

buttons.cellproperties.properties.widthUnit.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.cellproperties.properties.widthUnit.defaultValue

   Data type
         string

   Description
         Default selected unit in the row width unit selector in the cell
         properties and column cells properties edition dialogues. Possible
         values are: %, px or em

         Default: %



.. _buttons-cellproperties-properties-widthunit-removeitems:

buttons.cellproperties.properties.widthUnit.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.cellproperties.properties.widthUnit.removeItems

   Data type
         list of strings

   Description
         List of items to remove from the row width unit selector in the cell
         properties and column cells properties edition dialogues. Key list is:
         %, px, em

         Default: void



.. _buttons-cellproperties-properties-height-defaultvalue:

buttons.cellproperties.properties.height.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.cellproperties.properties.height.defaultValue

   Data type
         +int

   Description
         Default value of the row height in the cell properties and column
         cells properties edition dialogues.

         Default: void



.. _buttons-cellproperties-properties-heightunit-defaultvalue:

buttons.cellproperties.properties.heightUnit.defaultValue
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.cellproperties.properties.heightUnit.defaultValue

   Data type
         string

   Description
         Default selected unit in the row height unit selector in the cell
         properties and column cells properties edition dialogues. Possible
         values are: %, px or em

         Default: %



.. _buttons-cellproperties-properties-heightunit-removeitems:

buttons.cellproperties.properties.heightUnit.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.cellproperties.properties.heightUnit.removeItems

   Data type
         list of strings

   Description
         List of items to remove from the row height unit selector in the cell
         properties and column cells properties edition dialogues. Key list is:
         %, px, em

         Default: void



.. _buttons-cellproperties-properties-borderstyle-removeitems:

buttons.cellproperties.properties.borderStyle.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.cellproperties.properties.borderStyle.removeItems

   Data type
         list of strings

   Description
         List of items to remove from the cell border style selector in the
         cell properties and column cells properties edition dialogues. Key
         list is: not set, none, dotted, dashed, solid, double, groove, ridge,
         inset, outset

         Default: void



.. _buttons-pastetoggle-setactiveonrteopen:

buttons.pastetoggle.setActiveOnRteOpen
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.pastetoggle.setActiveOnRteOpen

   Data type
         boolean

   Description
         If set, and if the pastetoggle button is enabled, the button is
         toggled to ON when the RTE opens.

         Default : 0



.. _buttons-pastetoggle-hidden:

buttons.pastetoggle.hidden
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.pastetoggle.hidden

   Data type
         boolean

   Description
         If set, and if the pastetoggle button is enabled, the button is hidden
         in both the toolbar and the context menu. Hence, if
         buttons.pastetoggle.setActiveOnRteOpen is also set, all paste
         operations will be performed using the set clean paste behaviour.

         Default : 0

         Note: For BE operations, the default or overriding clean paste
         behaviour may be set in User TSconfig.



.. _buttons-pastebehaviour-behaviour-keeptags:

buttons.pastebehaviour.[ *behaviour* ].keepTags
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.pastebehaviour.[ *behaviour* ].keepTags

   Data type
         list of strings

   Description
         List of tags to be kept when pasting content while the specified
         behaviour is enabled. The behaviour may be pasteStructure or
         pasteFormat.

         Default:

         \- for pasteStructure: a, p, h[0-6], pre, address, article, aside,
         blockquote, div, footer, header, nav, section, hr, br, table, thead,
         tbody, tfoot, caption, tr, th, td, ul, ol, dl, li, dt, dd

         \- for pasteFormat: a, p, h[0-6], pre, address, article, aside,
         blockquote, div, footer, header, nav, section, hr, br, table, thead,
         tbody, tfoot, caption, tr, th, td, ul, ol, dl, li, dt, dd, b, bdo,
         big, cite, code, del, dfn, em, i, ins, kbd, label, q, samp, small,
         strike, strong, sub, sup, tt, u, var



.. _buttons-pastebehaviour-behaviour-removeattributes:

buttons.pastebehaviour.[ *behaviour* ].removeAttributes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         buttons.pastebehaviour.[ *behaviour* ].removeAttributes

   Data type
         list of strings

   Description
         List of attributes to be removed from all tags when pasting content
         while the specified behaviour is enabled. The behaviour may be
         pasteStructure or pasteFormat.

         Default:

         \- for pasteStructure: id, on\*, style, class, className, lang, align,
         valign, bgcolor, color, border, face, .\*:.\*

         \- for pasteFormat: id, on\*, style, class, className, lang, align,
         valign, bgcolor, color, border, face, .\*:.\*



.. _rteheightoverride:

RTEHeightOverride
~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         RTEHeightOverride

   Data type
         int+

   Description
         If set, the specified value will override the calculated height of the
         RTE. This includes the height of the toolbar, of the editing area and
         of the status bar.

         See also User TSconfig options.RTESmallHeight and
         options.RTELargeHeightIncrement

         Note: This property may be overridden by the BE user configuration.
         See User TSconfig.



.. _rtewidthoverride:

RTEWidthOverride
~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         RTEWidthOverride

   Data type
         int+/%

   Description
         If set, the specified value will override the calculated width of the
         RTE editing area. Note that a percentage may be specified.

         Note: The property is ignored in IE if the value is a percentage.

         Note: This property may be overridden by the BE user configuration.
         See User TSconfig.



.. _rteresize:

rteResize
~~~~~~~~~

.. container:: table-row

   Property
         rteResize

   Data type
         boolean

   Description
         If set, the RTE is resizable.

         Default: 0

         Note: This property may be overridden by the BE user configuration.
         See User TSconfig.



.. _rtemaxheight:

rteMaxHeight
~~~~~~~~~~~~

.. container:: table-row

   Property
         rteMaxHeight

   Data type
         int+

   Description
         If the RTE is resizable, this is the maximal height of the RTE,
         including the tool bar, the editing area and the status bar.

         Default: 2000

         Note: This property may be overridden by the BE user configuration.
         See User TSconfig.



.. _dialoguewindows-defaultpositionfromtop:

dialogueWindows.defaultPositionFromTop
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         dialogueWindows.defaultPositionFromTop

   Data type
         int+

   Description
         The default opening position from the top of the screen of a dialogue
         window opened when a button is pressed.

         Note: May be averridden by a specific button configuration.



.. _dialoguewindows-defaultpositionfromleft:

dialogueWindows.defaultPositionFromLeft
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         dialogueWindows.defaultPositionFromLeft

   Data type
         int+

   Description
         The default opening position from the left of the screen of a dialogue
         window opened when a button is pressed.

         Note: May be averridden by a specific button configuration.



.. _dialoguewindows-donotresize:

dialogueWindows.doNotResize
~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         dialogueWindows.doNotResize

   Data type
         boolean

   Description
         If set, the window that is opened when any button is pressed will not
         be resized to its contents.

         Default: 0



.. _dialoguewindows-donotcenter:

dialogueWindows.doNotCenter
~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         dialogueWindows.doNotCenter

   Data type
         boolean

   Description
         If set, the window that is opened when any button is pressed will not
         be centered in the parent window.

         Default: 0



.. _userelements:

userElements.[#]
~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         userElements.[#]

   Data type
         string/->userCategory

   Description
         Configuration of the categories of user elements

         The string value sets the name of the category. Value is language-
         splitted (by \|) to allow for multiple languages.



.. _logdeprecatedproperties-disabled:

logDeprecatedProperties.disabled
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         logDeprecatedProperties.disabled

   Data type
         boolean

   Description
         If set, usage of deprecated Page TS Config properties is not logged to
         the deprecation log.

         Default: 0



.. _logdeprecatedproperties-logalsotobelog:

logDeprecatedProperties.logAlsoToBELog
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         logDeprecatedProperties.logAlsoToBELog

   Data type
         boolean

   Description
         If set, usage of deprecated Page TS Config properties is also logged
         to the BE log.

         Default: 0



.. _schema-sources:

schema.sources.[#]
~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         schema.sources.[#]

   Data type
         array

   Description
         An array of filenames containing vocabulary definitions inXML/RDF
         format.

         Default: schemaOrg =
         EXT:rtehtmlarea/extensions/MicrodataSchema/res/schemaOrgAll.rdf


[page:RTE.default/RTE.default.FE/RTE.config.(table).(field)/RTE.config
.(table).(field).types.(type)]


