.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _faq:

Frequently Asked Questions
--------------------------


.. _faq-remove-a-link:

How do I remove a link?
^^^^^^^^^^^^^^^^^^^^^^^

To remove a link, select the link and click on the "Insert/Modify
link" button. At the top of the popup window, you have the option to
remove the link.


.. _faq-delete-a-table:

How do I delete a table?
^^^^^^^^^^^^^^^^^^^^^^^^

You may proceed as follows:

- click in any cell in the table;

- in the editor status bar, displayed at the bottom of the editor frame,
  click on "table";

- press the "Delete" key or the "Backspace" key.

You may also proceed as follows:

- click in any cell in the table;

- click on the right button of the mouse or pointing device;

- the context menu is displayed;

- at the bottom of the context menu, you have the option to delete the
  TABLE element.

In Internet Explorer, you may also proceed as follows:

- put the cursor just after the table and press the "Backspace" key;

- or click on the border of the table and press the "Delete" key.


.. _faq-use-my-css-styles:

How do I configure the editor to use my CSS styles?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The following style sheets apply to the contents of the editing area
and are linked in the following order:

#. the htmlarea-edited-content.css file from the skin in use; it contains
   selectors for use in the editor, but not intended to be applied in the
   frontend;

#. the css file specified by property contentCSS in Page TSconfig: you
   may define the styles you want to use in an external CSS file and
   assign the file name to this property.


.. _faq-appearance-of-links:

Is it possible to style the appearance of links in the RTE itself?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

«In the front end, all links are rendered correctly, but in the RTE
itself the links are shown in standard color (blue) with underline,
except links I've already visited. These are in black with underline
and a hover effect. If I add new links, it's the same: Visited links
become black, unvisited blue. The rest of the style sheet is parsed
without problems. Any hints?»

This is a Mozilla/Firefox feature. In the editing area, the link
attributes defined in the browser user profile take precedence over
the corresponding attributes specified in your style sheet.
Apparently, these preferences cannot be neutral.

In an editing area displayed by Internet Explorer, the style sheet
specification is applied to the link.

You can force (not only) Firefox to take your style sheet rather than
the user preference settings via the !important rule. Check this out
for example:

::

   a:link, a:visited{
           text-decoration:none !important;
           color:#c00 !important;
   }

Now your links should turn red and the text-decoration should be gone.
If you are using the same style sheet in the frontend and in the RTE,
you can avoid forcing your link style on all frontend users by
restricting it to the RTE editing area. Using the same example as
above:

::

   .htmlarea-content-body a:link, .htmlarea-content-body a:visited {
           text-decoration:none !important;
           color:#c00 !important;
   }


.. _faq-bengali-font:

How can I use a Bengali Open Type font in the editor?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Since Bengali is not well supported by all browsers, the recommended
approach would be to add the Bengali font in the list of font families
specified on the body selector of the RTE.default.contentCSS
stylesheet. For example:

::

   body { font-family: Verdana, sans-serif, Likhan; }

For some reason, with some fonts, the lines may overlap when using
larger font sizes. It is the case with the Bengali Likhan font in
Firefox 1.0.2. This may also be corrected through the stylesheet. For
example:

::

   body { font-family: Verdana, sans-serif, Likhan; line-height: 1.4; }

Note that, when using the Bengali Likhan font, a line-height with em
or % unit may not produce any effect in Firefox 1.0.2.


.. _faq-class-attribute-on-table-tags:

Why is the class attribute on table tags always rendered as contenttable in the front end?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

«TYPO3 always replaces the class I selected in the RTE for a table
with the class "contenttable". Do you have an idea how to switch that
off?»

Assuming that you have installed extension CSS Styled Content
(css\_styled\_content), add the following line in your TS template
Setup field:

::

   lib.parseFunc_RTE.externalBlocks.table.stdWrap.HTMLparser.tags.table.fixAttrib.class.list >

The contenttable class will then be added only if no class is
specified for the table.


.. _faq-abbr-and-acronym-tags:

Why are abbr and acronym tags not correctly rendered in the front end?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Make sure that abbr and acronym are included in the list:

::

   styles.content.links.allowTags

in your TS template constants.


.. _faq-editor-not-displayed-full-width:

Why is the editor not displayed with full width when I use the full window wizard?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you are editing the most usual content element, that is the
bodytext column of tt\_content table, try to add the following line to
your Page TSconfig:

::

   TCEFORM.tt_content.bodytext.RTEfullScreenWidth = 100%

Note that this setting is now included in the default configuration of
the extension.

If editing some other column, use the same model:

::

   TCEFORM.my_table_name.my_column_name.RTEfullScreenWidth = 100%


.. _faq-selector-boxes-disabled:

Why do style selector boxes remain disabled in IE?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

When using IE, make sure that the browser cache setting is set to
Automatic.


.. _faq-all-buttons-displayed:

Why can't I get all buttons to be displayed?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

#. User TSconfig restricts the user to a specified set of buttons;
   therefore, for access to all buttons without restriction, in User
   TSconfig, set: options.RTEkeyList = \*

#. Page TSconfig adds the buttons required to edit the table and field
   you wish to edit; therefore, to add all buttons by default, in Page
   TSconfig, set RTE.default.showButtons = \*

#. If you are trying to edit the bodytext field of a content element from
   table tt\_content, then the TCA field types and palettes may specify a
   list of buttons to add; this specification takes precedence over
   RTE.default.showButtons; to override any such setting in TCA for the
   bodytext field of table tt\_content, in Page TSconfig, set
   RTE.config.tt\_content.bodytext.showButtons = \*

#. If you are trying to edit a text field from another table, then, in
   Page TSconfig, set RTE.config.tableName.columnName.showButtons = \*

#. Buttons textcolor, bgcolor, fontstyle and fontsize are enabled only if
   «Enable features that use the style attribute> is checked in the
   extension manager.

#. If extension static\_info\_tables is not installed, the spellcheck,
   language and acronym buttons are not enabled.

#. If the encoding of the content element is not either iso-8859-1 or
   utf-8, the spellcheck button is not enabled.

#. Buttons user and acronym are never available in the front end.

#. Button unlink is not available if button link is not available.

#. None of the table operations buttons are available is the button table
   is not available.

#. Safari does not support the paste button.

#. Opera does not support the copy, cut and paste buttons.


.. _faq-long-to-load:

Why does it take so long to load the editor in Internet Explorer?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Try to adjust your IE browser cache settings:

#. From the IE main menu, navigate to: Tools -> Internet Options ->
   General -> button: Configure... or in some other IE versions: Extras
   -> Internet Options : Temporary Files -> button: Advanced

#. Select the radio button Automatic.

Some server configuration settings may also help working around
Internet Explorer caching problems. See the Server Configuration
section and the Tutorial section of this document.

