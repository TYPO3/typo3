.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt


.. _fonts:

fonts:
""""""

Properties of each font available in the RTE.


.. _fonts-id-string:

fonts.[ *id-string* ]
~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         fonts.[ *id-string* ]
   
   Description
         Defines the fonts available in the RTE.
         
         Properties:
         
         ::
         
            .name = Label of the font in menu (may be a reference to an entry in a localization file of the form LLL:EXT:[fileref]:[labelkey])
            .value = The font face value (comma-separated list of font-family names; if a font-family name contains spaces, it should be quoted with single quotes)
         
         Example:
         
         ::
         
            # General configuration of the available fonts:
            RTE.fonts {
              face1 {
                name = Verdana
                value = verdana, arial
              }
              face2 {
                name = Comic Sans
                value = 'Comic Sans MS'
              }
              noFace {
                name = No font
                value = 
              }
            }
            # Specific setting for the fontstyle selector:
            RTE.default.buttons.fontstyle.addItems = face2 , face1, noFace


[page:RTE]

