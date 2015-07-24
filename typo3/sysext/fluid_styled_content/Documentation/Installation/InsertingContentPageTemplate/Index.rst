.. include:: ../../Includes.txt

.. _inserting-content-page-template:

=======================================
Inserting content in your page template
=======================================

To get the different columns from the backend displayed in the frontend you can use
predefined CONTENT objects. Depending on the page templating you are using you would do
something in your TypoScript template like the following examples.


.. _inserting-content-page-template-template:

Based on the TEMPLATE content object (cObj)
===========================================

.. code-block:: typoscript

   page = PAGE
   page {
      10 = TEMPLATE
      10 {
         file = your/html/template.html
         subparts {
            MAIN_CONTENT = CONTENT
            MAIN_CONTENT {
               table = tt_content
               select {
                  orderBy = sorting
                  where = colPos=0
                  languageField = sys_language_uid
               }
            }
         }
      }
   }


.. _inserting-content-page-template-fluidtemplate:

Based on the FLUIDTEMPLATE content object (cObj)
================================================

.. code-block:: typoscript

   page = PAGE
   page {
      10 = FLUIDTEMPLATE
      10 {
         file = your/html/template.html
         variables {
            content = CONTENT
            content {
               table = tt_content
               select {
                  orderBy = sorting
                  where = colPos=0
                  languageField = sys_language_uid
               }
            }
         }
      }
   }

.. seealso::

   See the note about the removal of the predefined CONTENT objects like
   :typoscript:`styles.content.get` at :ref:`upgrading`.

