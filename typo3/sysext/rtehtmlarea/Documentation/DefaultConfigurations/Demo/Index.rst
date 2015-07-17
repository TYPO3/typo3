.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _demo-configuration:

Demo default configuration
--------------------------

This default configuration sets Page TSconfig and User TSconfig with
as many features as possible for users who want to explore the
features of the RTE. This is  **not** recommended for production
environments.


.. _demo-page-tsconfig:

The following is inserted in Page TSconfig:
"""""""""""""""""""""""""""""""""""""""""""

## Define labels and styles to be applied to class selectors in the
interface of the RTE

## Partial re-use of color scheme and frame scheme from CSS Styled
Content extension

::

   RTE.classes {
           align-left {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_tooltips.xlf:justifyleft
                   value = text-align: left;
           }
           align-center {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_tooltips.xlf:justifycenter
                   value = text-align: center;
           }
           align-right {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_tooltips.xlf:justifyright
                   value = text-align: right;
           }
           csc-frame-frame1 {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_contentcss.xlf:frame-frame1
                   value = background-color: #EDEBF1; border: 1px solid #333333;
           }
           csc-frame-frame2 {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_contentcss.xlf:frame-frame2
                   value = background-color: #F5FFAA; border: 1px solid #333333;
           }
           important {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_contentcss.xlf:important
                   value = color: #8A0020;
           }
           name-of-person {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_contentcss.xlf:name-of-person
                   value = color: #10007B;
           }
           detail {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_contentcss.xlf:detail
                   value = color: #186900;
           }
           component-items {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_contentcss.xlf:component-items
                   value = color: #186900;
           }
           action-items {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_contentcss.xlf:action-items
                   value = color: #8A0020;
           }
           component-items-ordered {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_contentcss.xlf:component-items
                   value = color: #186900;
           }
           action-items-ordered {
                   name = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_contentcss.xlf:action-items
                   value = color: #8A0020;
           }
   }

## Anchor classes configuration for use by the anchor accessibility
feature

::

   RTE.classesAnchor {
           externalLink {
                   class = external-link
                   type = url
                   image = EXT:rtehtmlarea/Resources/Public/Images/external_link.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:external_link_altText
                   titleText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:external_link_titleText
           }
           externalLinkInNewWindow {
                   class = external-link-new-window
                   type = url
                   image = EXT:rtehtmlarea/Resources/Public/Images/external_link_new_window.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:external_link_new_window_altText
                   titleText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:external_link_new_window_titleText
           }
           internalLink {
                   class = internal-link
                   type = page
                   image = EXT:rtehtmlarea/Resources/Public/Images/internal_link.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:internal_link_altText
                   titleText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:internal_link_titleText
           }
           internalLinkInNewWindow {
                   class = internal-link-new-window
                   type = page
                   image = EXT:rtehtmlarea/Resources/Public/Images/internal_link_new_window.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:internal_link_new_window_altText
                   titleText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:internal_link_new_window_titleText
           }
           download {
                   class = download
                   type = file
                   image = EXT:rtehtmlarea/Resources/Public/Images/download.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:download_altText
                   titleText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:download_titleText
           }
           mail {
                   class = mail
                   type = mail
                   image = EXT:rtehtmlarea/Resources/Public/Images/mail.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:mail_altText
                   titleText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:mail_titleText
           }
   }

## Default RTE configuration

::

   RTE.default {

## Markup options

::

      enableWordClean = 1
      removeTrailingBR = 1
      removeComments = 1
      removeTags = center, o:p, sdfield
      removeTagsAndContents = link, meta, script, style, title

## Allow img tags

::

      proc.entryHTMLparser_db.tags.img >

## Allow style attributes on p and span tags

::

      proc.entryHTMLparser_db.tags {
              p.allowedAttribs := addToList(style)
              span.fixAttrib.style.unset >
      }

## Toolbar options

::

      showButtons = *

## More toolbar options

::

      keepButtonGroupTogether = 1

## Enable status bar

::

      showStatusBar = 1

           )

## For this demo, do not remove font, strike and u tags

::

      proc.entryHTMLparser_db.removeTags := removeFromList(font,strike,u)


## List all class selectors that are allowed on the way to the
database

::

      proc.allowedClasses = external-link, external-link-new-window, internal-link, internal-link-new-window, download, mail
      proc.allowedClasses := addToList(align-left, align-center, align-right, align-justify)
      proc.allowedClasses := addToList(csc-frame-frame1, csc-frame-frame2)
      proc.allowedClasses := addToList(component-items, action-items)
      proc.allowedClasses := addToList(component-items-ordered, action-items-ordered)
      proc.allowedClasses := addToList(important, name-of-person, detail)
      proc.allowedClasses := addToList(indent)

## Restrict the list of class selectors presented by the RTE to the
following for the specified tags:

::

      buttons.blockstyle.tags.div.allowedClasses = align-left, align-center, align-right
      buttons.blockstyle.tags.div.allowedClasses := addToList(csc-frame-frame1, csc-frame-frame2)
      buttons.blockstyle.tags.table.allowedClasses = csc-frame-frame1, csc-frame-frame2
      buttons.blockstyle.tags.td.allowedClasses = align-left, align-center, align-right
      buttons.textstyle.tags.span.allowedClasses = important, name-of-person, detail

## Configuration of classes for links

## These classes should also be in the list proc.allowedClasses

::

      buttons.link.properties.class.allowedClasses = external-link, external-link-new-window, internal-link, internal-link-new-window, download, mail
      buttons.link.page.properties.class.default = internal-link
      buttons.link.url.properties.class.default = external-link-new-window
      buttons.link.file.properties.class.default = download
      buttons.link.mail.properties.class.default = mail

## Show all applicable class selectors available in the style sheet
file

::

      buttons.blockstyle.showTagFreeClasses = 1
      buttons.textstyle.showTagFreeClasses = 1

## Configuration specific to the table button or TableOperations
feature

## Use the context menu instead of the toolbar for table operations,
but keep toggleborders button in toolbar

## Show borders on table creation

::

      hideTableOperationsInToolbar = 1
      buttons.toggleborders.keepInToolbar = 1
      buttons.toggleborders.setOnTableCreation = 1

## Configuration specific to the inserttag button or QuickTag feature

## Do not allow insertion of the following tags

::

      buttons.inserttag.denyTags = font, underline, strike, table

## Configuration specific to the bold and italic buttons

## Add hotkeys associated with bold, italic, strikethrough and
underline buttons

::

      buttons.bold.hotKey = b
      buttons.italic.hotKey = i
      buttons.strikethrough.hotKey = s
      buttons.underline.hotkey = u

## Configuration specific to the spellcheck button or SpellCheck
feature

## Enable the use of personal dictionaries

::

      buttons.spellcheck.enablePersonalDictionaries = 1

## Configuration of microdata schema

::

      schema {
                   sources {
                           schemaOrg = EXT:rtehtmlarea/extensions/MicrodataSchema/res/schemaOrgAll.rdf
                   }
           }
   }

## Use same processing as on entry to database to clean content pasted
into the editor

::

   RTE.default.enableWordClean.HTMLparser < RTE.default.proc.entryHTMLparser_db

## front end RTE configuration

::

   RTE.default.FE < RTE.default
   RTE.default.FE.userElements >

## tt\_content TCEFORM configuration

## Let use all the space available for more comfort.

::

   TCEFORM.tt_content.bodytext.RTEfullScreenWidth = 100%


.. _the-following-is-inserted-in-user-tsconfig:

The following is inserted in User TSconfig:
"""""""""""""""""""""""""""""""""""""""""""

## Enable the RTE by default for all users

::

   setup.default.edit_RTE = 1

## Enable the file upload feature of the element browser by default
for all users

::

   options.uploadFieldsInTopOfEB = 1

## Set the default spelling ability of the check speller for all users

::

   options.HTMLAreaPspellMode = bad-spellers

## Enable the personal dictionary feature of the check speller by
default for all users

::

   options.enablePersonalDicts = 1



