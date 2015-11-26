.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _default-configuration:

Default configuration of RTE content transformation
---------------------------------------------------

This default configuration establishes default settings in Page
TSconfig for RTE content transformation.

For documentation of RTE tranformations, see:
:ref:`RTE tranformations <t3api:transformations>`

For documentation of Page TSconfig configuration of RTE processing, see:
:ref:`RTE tranformations Page TSconfig <t3api:transformations-tsconfig>`

For documentation of the HTMLparser, see:
:ref:`TypoScript HTMLparser <t3tsref:htmlparser>`

For documentation of RTE settings in TCA, see:
:ref:`Special Configuration Options <t3tca:special-configuration-options>`


.. _default-configuration-page-tsconfig:

The following is inserted in Page TSconfig:
"""""""""""""""""""""""""""""""""""""""""""

## Default RTE processing rules

::

   RTE.default.proc {

## TRANSFORMATION METHOD

## We assume that CSS Styled Content is used.

::

      overruleMode = ts_css

## DO NOT CONVERT BR TAGS INTO LINEBREAKS

## br tags in the content are assumed to be intentional.

::

      dontConvBRtoParagraph = 1

## PRESERVE DIV SECTIONS - DO NOT REMAP TO P

::

      preserveDIVSections = 1

## TAGS ALLOWED OUTSIDE P & DIV

::

      allowTagsOutside = address, article, aside, blockquote, footer, header, hr, nav, section

## TAGS ALLOWED

## Added to the default internal list: b,i,u,a,img,br,div,center,pre,f
ont,hr,sub,sup,p,strong,em,li,ul,ol,blockquote,strike,span

## But, for the sake of clarity, we use a complete list in alphabetic
order.

## center, font, link, meta, o:p, strike, sdfield, style, title and u
will be removed on entry (see below).

## b and i will be remapped on exit (see below).

## Note that the link accessibility feature of htmlArea RTE does
insert img tags.

::

      allowTags = a, abbr, acronym, address, article, aside, b, bdo, big, blockquote, br, caption, center, cite, code, col, colgroup, dd, del, dfn, dl, div, dt, em, font, footer
      allowTags := addToList(header, h1, h2, h3, h4, h5, h6, hr, i, img, ins, kbd, label, li, link, meta, nav, ol, p, pre, q, samp, sdfield, section, small)
      allowTags := addToList(span, strike, strong, style, sub, sup, table, thead, tbody, tfoot, td, th, tr, title, tt, u, ul, var)

## TAGS DENIED

## Make sure we can set rules on any tag listed in allowTags.

::

      denyTags >

## ALLOWED P & DIV ATTRIBUTES

## Attributes class and align are always preserved

## Align attribute will be unset on entry (see below)

## This is a list of additional attributes to keep

::

      keepPDIVattribs = id, title, dir, lang, xml:lang, itemscope, itemtype, itemprop

## ALLOW TO WRITE ABOUT HTML

::

      dontUndoHSC_db = 1
      dontHSC_rte = 1

## CONTENT TO DATABASE

::

      entryHTMLparser_db = 1
      entryHTMLparser_db {

## TAGS ALLOWED

## Always use the same list of allowed tags.

::

              allowTags < RTE.default.proc.allowTags

## TAGS DENIED

## Make sure we can set rules on any tag listed in allowTags.

::

              denyTags >

## AVOID CONTENT BEING HSC'ed TWICE

::

              htmlSpecialChars = 0

::

              tags {

## REMOVE IMG TAGS

::

                      img.allowedAttribs = 0
                           img.rmTagIfNoAttrib = 1

## CLEAN ATTRIBUTES ON THE FOLLOWING TAGS

::

                      span.fixAttrib.style.unset = 1
                           span.allowedAttribs = id, title, dir, lang, xml:lang, class, itemscope, itemtype, itemprop
                           span.rmTagIfNoAttrib = 1
                           p {
                                   allowedAttribs = id, title, dir, lang, xml:lang, class, itemscope, itemtype, itemprop
                                   fixAttrib.align.unset = 1
                           }
                           div < .p
                           hr.allowedAttribs = class
                           b.allowedAttribs  < .span.allowedAttribs
                           bdo.allowedAttribs  < .span.allowedAttribs
                           big.allowedAttribs  < .span.allowedAttribs
                           blockquote.allowedAttribs  < .span.allowedAttribs
                           cite.allowedAttribs  < .span.allowedAttribs
                           code.allowedAttribs  < .span.allowedAttribs
                           del.allowedAttribs  < .span.allowedAttribs
                           dfn.allowedAttribs  < .span.allowedAttribs
                           em.allowedAttribs  < .span.allowedAttribs
                           i.allowedAttribs  < .span.allowedAttribs
                           ins.allowedAttribs  < .span.allowedAttribs
                           kbd.allowedAttribs  < .span.allowedAttribs
                           label.allowedAttribs  < .span.allowedAttribs
                           q.allowedAttribs  < .span.allowedAttribs
                           samp.allowedAttribs  < .span.allowedAttribs
                           small.allowedAttribs  < .span.allowedAttribs
                           strike.allowedAttribs  < .span.allowedAttribs
                           strong.allowedAttribs  < .span.allowedAttribs
                           sub.allowedAttribs  < .span.allowedAttribs
                           sup.allowedAttribs  < .span.allowedAttribs
                           tt.allowedAttribs  < .span.allowedAttribs
                           u.allowedAttribs  < .span.allowedAttribs
                           var.allowedAttribs  < .span.allowedAttribs
                   }

## REMOVE OPEN OFFICE META DATA TAGS, WORD 2003 TAGS, LINK, META,
STYLE AND TITLE TAGS, AND DEPRECATED HTML TAGS

## We use this rule instead of the denyTags rule so that we can
protect custom tags without protecting these unwanted tags.

::

              removeTags = center, font, link, o:p, sdfield, meta, style, title, strike, u

## PROTECT CUSTOM TAGS

::

              keepNonMatchedTags = protect
           }

::

      HTMLparser_db {

## STRIP ALL ATTRIBUTES FROM THESE TAGS

## If this list of tags is not set, it will default to:
b,i,u,br,center,hr,sub,sup,strong,em,li,ul,ol,blockquote,strike.

## However, we want to keep xml:lang attribute on most tags and tags
from the default list where cleaned on entry.

::

              noAttrib = br
           }

::

      exitHTMLparser_db = 1
           exitHTMLparser_db {

## KEEP ALL TAGS

## Unwanted tags were removed on entry.

## Without this rule, the parser will remove all tags! Presumably,
this rule will be more efficient than repeating the allowTags rule

::

              keepNonMatchedTags = 1

## AVOID CONTENT BEING HSC'ed TWICE

::

              htmlSpecialChars = 0
           }
   }

## Use same RTE processing rules in FE

::

   RTE.default.FE.proc < RTE.default.proc

## RTE processing rules for bodytext column of tt\_content table

## Erase settings from other extensions

::

   RTE.config.tt_content.bodytext >

## Make sure we use ts\_css transformation

::

   RTE.config.tt_content.bodytext.proc.overruleMode = ts_css
   RTE.config.tt_content.bodytext.types.text.proc.overruleMode = ts_css
   RTE.config.tt_content.bodytext.types.textpic.proc.overruleMode = ts_css



