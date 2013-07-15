.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt


.. _fontsizes:

fontSizes:
""""""""""

Properties of each font size available in the RTE.


.. _fontsizes-id-string:

fontSizes.[ *id-string* ]
~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         fontSizes.[ *id-string* ]
   
   Description
         Defines the font sizes available in the RTE.
         
         Properties:
         
         ::
         
            .name = Label of the font size in menu (may be a reference to an entry in a localization file of the form LLL:EXT:[fileref]:[labelkey])
            .value = The font size value
         
         Example:
         
         ::
         
            # General configuration of the available font sizes:
            RTE.fontSizes {
              size1 {
                name = Large
                value = 16px
              }
              size2 {
                name = Small
                value = 8px
              }
            }
            # Specific setting for the fontsize selector:
            RTE.default.buttons.fontsize.addItems = size1, size2


[page:RTE]

