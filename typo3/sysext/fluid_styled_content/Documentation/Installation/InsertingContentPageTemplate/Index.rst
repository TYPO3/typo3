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
                  where = {#colPos}=0
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

   lib.dynamicContent = COA
   lib.dynamicContent {
      10 = LOAD_REGISTER
      10.colPos.cObject = TEXT
      10.colPos.cObject {
         field = colPos
         ifEmpty.cObject = TEXT
         ifEmpty.cObject {
            value.current = 1
            ifEmpty = 0
         }
      }
      20 = CONTENT
      20 {
         table = tt_content
         select {
            orderBy = sorting
            where = colPos={register:colPos}
            where.insertData = 1
         }
      }
      90 = RESTORE_REGISTER
   }

   page = PAGE
   page {
      10 = FLUIDTEMPLATE
      10 {
         templateName = Default
         templateRootPaths {
            0 = EXT:example_package/Resources/Private/Templates/Page/
         }
         partialRootPaths {
            0 = EXT:example_package/Resources/Private/Partials/Page/
         }
         layoutRootPaths {
            0 = EXT:example_package/Resources/Private/Layouts/Page/
         }
      }
   }

.. code-block:: html

   <f:layout name="Default" />
   <f:section name="Main">

      <f:cObject typoscriptObjectPath="lib.dynamicContent" data="{colPos: '0'}" />

   </f:section>


.. seealso::

   See the note about the removal of the predefined CONTENT objects like
   :typoscript:`styles.content.get` at :ref:`upgrading`.

