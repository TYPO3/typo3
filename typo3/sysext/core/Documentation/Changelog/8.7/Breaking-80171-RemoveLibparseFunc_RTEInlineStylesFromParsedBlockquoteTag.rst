.. include:: /Includes.rst.txt

====================================================================================
Breaking: #80171 - Remove lib.parseFunc_RTE inline styles from parsed blockquote tag
====================================================================================

See :issue:`80171`

Description
===========

With https://forge.typo3.org/issues/44879 the inline styles were removed from CSC.
However, the change was not applied to FSC.


Impact
======

:typoscript:`lib.parseFunc_RTE` doesn't overwrite the attributes of :html:`<blockquote/>`
and doesn't add the style attribute anymore.


Affected Installations
======================

All installations using the :typoscript:`lib.parseFunc_RTE` provided by
EXT:fluid_styled_content where

.. code-block:: typoscript

   lib.parseFunc_RTE {
      externalBlocks {
         blockquote {
            callRecursive {
               tagStdWrap {
                  HTMLparser = 1
                  HTMLparser.tags.blockquote.overrideAttribs = style="margin-bottom:0;margin-top:0;"
               }
            }
         }
      }
   }

was not overwritten manually.


Migration
=========

If your frontend relies on this inline CSS styles, make sure to add following CSS on
your own:

.. code-block:: css

   blockquote {
      margin-top: 0;
      margin-bottom: 0;
   }

Additionally you have to check if some other attributes are now added/preserved to
:html:`<blockquote/>` due the fact, that the former typoscript has removed all
attributes before adding the style attribute.

.. index:: Frontend, TypoScript, ext:fluid_styled_content, RTE
