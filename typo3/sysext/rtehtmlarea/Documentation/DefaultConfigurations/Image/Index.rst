.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _image-configuration:

Image default configuration
---------------------------

This default configuration establishes support for images in Page
TSconfig. This is for use in addition to the Typical default
configuration.


.. _image-page-tsconfig:

The following is inserted in Page TSconfig:
"""""""""""""""""""""""""""""""""""""""""""

## Anchor classes configuration for use by the anchor accesibility
feature

## Add inline icons to the links

::

   RTE.classesAnchor {
           externalLink {
                   image = EXT:rtehtmlarea/Resources/Public/Images/external_link.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:external_link_altText
           }
           externalLinkInNewWindow {
                   image = EXT:rtehtmlarea/Resources/Public/Images/external_link_new_window.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:external_link_new_window_altText
           }
           internalLink {
                   image = EXT:rtehtmlarea/Resources/Public/Images/internal_link.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:internal_link_altText
           }
           internalLinkInNewWindow {
                   image = EXT:rtehtmlarea/Resources/Public/Images/internal_link_new_window.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:internal_link_new_window_altText
           }
           download {
                   image = EXT:rtehtmlarea/Resources/Public/Images/download.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:download_altText
           }
           mail {
                   image = EXT:rtehtmlarea/Resources/Public/Images/mail.gif
                   altText = LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_accessibilityicons.xlf:mail_altText
           }
   }

## Default RTE configuration

::

   RTE.default {

                   ## Enable the image button
           showButtons := addToList(image)

                   ## Tags allowed outside p and div
                   ## Adding img tag to the default list
           proc.allowTagsOutside := addToList(img)

                   ## Do not remove img tags
           proc.entryHTMLparser_db.tags.img >
   }

   RTE.default.FE.showButtons < RTE.default.showButtons
   RTE.default.FE.proc.allowTagsOutside < RTE.default.proc.allowTagsOutside
   RTE.default.FE.proc.entryHTMLparser_db.tags.img >


