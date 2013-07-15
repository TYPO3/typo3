.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt


.. _usercategory:

userCategory:
"""""""""""""

Properties of each user element category.


.. _load:

load
~~~~

.. container:: table-row

   Property
         load

   Data type
         string

   Description
         If set, the a predefined set of user element is loaded into this
         category. They are always loaded in the key starting with 100 and then
         forward in steps of 10.

         Current options are:

         "images\_from\_folder": Loads gif,jpg,jpeg,png images from the
         specified folder (defined by the .path property)



.. _merge:

merge
~~~~~

.. container:: table-row

   Property
         merge

   Data type
         Boolean

   Description
         If set, then any manually configured user elements are merged onto the
         ones loaded by the .load operation.



.. _path:

path
~~~~

.. container:: table-row

   Property
         path

   Data type
         String

   Description
         *(Applies for load=images\_from\_folder only)*

         Sets the path of the folder from which to fetch the images
         (gif,jpg,jpeg,png)

         **Example:**

         .path = fileadmin/istate/



.. _user-elements:

[key]
~~~~~

.. container:: table-row

   Property
         [key]

   Data type
         string/ :ref:`userElements <userelements-configuration>`

   Description
         Configuration of the user elements.

         The string value is the name of the user element. Language-splitted.

         **Example:**

         ::

            RTE.default.userElements {
                # Category with various elements
              10 = Various elements | Diverse elements
              10 {
                  # An image is inserted
                1 = Logo 1 | Bomærke 1
                1.description = This is the logo number 1. | Dette er logo nummer 1
                1.content = <img src="###_URL###fileadmin/istate/curro.png">

                  # The text-selection is wrapped with <sup> tags.
                2 = Subscript
                2.description = Selected text is wrapped in <sup>-tags.
                2.mode = wrap
                2.content = <sup>|</sup>

                  # This submits the selected text content to the script, rte_cleaner.php
                5 = Strip all tags
                5.description = All HTML-codes are removed from selection.
                5.mode = processor
                5.submitToScript = typo3/rte_cleaner.php
              }

                # Category with images from the fileadmin/istate/ folder
              2.load = images_from_folder
              2.merge = 1
              2.path = fileadmin/istate/
                # here the logo from "Various elements" is included as well
              2.1 < .10.1
            }
              # Show the user-button, if not existing
            RTE.default.showButtons = user


[page:->userCategory]

