.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt


.. _colors-configuration:

colors:
"""""""

Properties of each color available in the RTE.


.. _colors-id-string:

colors.[ *id-string* ]
~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         colors.[ *id-string* ]

   Description
         Defines the colors available in the RTE.

         Properties:

         ::

            .name = Label of the color in menu
            .value = The HTML-color value

         Example:

         ::

            # General configuration of the available colors:
            RTE.colors {
              color1 {
                name = Background color
                value = blue
              }
              color2 {
                name = Another color I like!
                value = #775533
              }
              noColor {
                name = No color
                value =
              }
            }
            # Specific setting for the font color selector:
            RTE.default.colors = color1, color2, noColor


[page:RTE]

