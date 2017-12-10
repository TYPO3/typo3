.. include:: ../../Includes.txt

================================
Breaking: #81847 - Remove JSMENU
================================

See :issue:`81847`

Description
===========

The content objects :ts:`JSMENU` and :ts:`JSMENUITEM` to create a jump menu have been removed.
The used JavaScript is totally outdated and has not really been touched since its creation more than 10 years ago.

If this kind of menu is needed it can be easily achieved with a TMENU as well:

.. code-block:: typoscript

   lib.menu = HMENU
   lib.menu {
      1 = TMENU
      1 {
         wrap = <select onchange="window.location=this.options[this.selectedIndex].value">|</select>

         NO {
            doNotLinkIt = 1
            stdWrap.cObject = COA
               stdWrap.cObject {
               10 = TEXT
               10 {
                  wrap = <option value="{getIndpEnv:TYPO3_SITE_URL}|">
                  insertData = 1
                  typolink {
                     parameter.field = uid
                     returnLast = url
                     htmlSpecialChars = 1
                  }
               }
               20 = TEXT
               20 {
                  field = subtitle//title
                  htmlSpecialChars = 1
                  wrap =  |</option>
               }
            }
         }
      }
   }

.. index:: Frontend, TypoScript, NotScanned
