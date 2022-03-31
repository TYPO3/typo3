.. include:: /Includes.rst.txt

.. _inserting-content-page-template:

=======================================
Inserting content in your page template
=======================================

To get the different columns from the backend displayed in the frontend
you can use predefined :ref:`CONTENT <t3tsref:cobj-content>` objects or
the :ref:`DatabaseQueryProcessor <t3tsref:DatabaseQueryProcessor>`.

It is advised to make all your changes in a custom extension, visit the
:doc:`site package documentation <t3sitepackage:Index>` to find out more.

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
            where = {#colPos}={register:colPos}
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

Next step
=========

You can start :ref:`adding content elements <content-elements>` or make further
:ref:`configurations <configuration>`.
