.. include:: /Includes.rst.txt

========================================================================================
Breaking: #79622 - TypoScript Standard Header has been removed from Fluid Styled Content
========================================================================================

See :issue:`79622`

Description
===========

The TypoScript standard header rendering definition `lib.stdHeader` has been
introduced in CSS Styled Content to reference it across multiple content
elements to simplify maintenance.

For Fluid Styled Content a workaround for compatibility with CMS 7 has been introduced
to simplify migration. However, it only renders the header and misses all frames,
and additional are options necessary to generate a streamlined rendering
output if the content element layout was not implemented correctly.


Example
-------


TypoScript Configuration
^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: typoscript

   tt_content.simple_content = COA
   tt_content.simple_content {
      10 < lib.stdHeader
      20 = TEXT
      20.field = bodytext
   }


Generated Output
^^^^^^^^^^^^^^^^

.. code-block:: html

   <header>
      <h1>Nunc vel libero dignissim</h1>
   </header>
   <p>
      Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed cursus
      vel lectus vel placerat. Suspendisse non metus sed lorem sagittis
      consequat non vel nulla.
   </p>


Expected Output
^^^^^^^^^^^^^^^

.. code-block:: html

   <div id="c53" class="frame frame-default frame-type-header frame-layout-0">
      <a id="c62"></a>
      <header>
         <h1>Nunc vel libero dignissim</h1>
      </header>
      <p>
         Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed cursus
         vel lectus vel placerat. Suspendisse non metus sed lorem sagittis
         consequat non vel nulla.
      </p>
      <p>
         <a href="#top">Top</a>
      </p>
   </div>


Impact
======

Content elements using lib.stdHeader will miss the header in the frontend output.


Affected Installations
======================

All installations that have content elements relying on lib.stdHeader and use
Fluid Styled Content as content rendering definition.


Migration
=========

To fully embrace support for Fluid Styled Content the setup needs to be changed.
This is a very simple example, it is highly recommended to migrate content
elements and plugins to only use fluid. The default fluid layout has all
information necessary to render the header, frame and everything else
correctly. That means that you do not need to care about streamlined rendering.


Example for Content Element with TypoScript Rendering
-----------------------------------------------------

If you need TypoScript to process the rendering of your content element the
recommended way is to use a variable to pass the rendering to fluid.


TypoScript Setup
^^^^^^^^^^^^^^^^

.. code-block:: typoscript

   tt_content.simple_content =< lib.fluidContent
   tt_content.simple_content {
      templateName = SimpleContent
      templateRootPaths.12022017 = EXT:my_simple_content/Resources/Private/Templates/
      variables.content = COA
      variables.content {
         10 = TEXT
         10.field = bodytext
      }
   }


Fluid Template
^^^^^^^^^^^^^^

.. code-block:: html

   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers" data-namespace-typo3-fluid="true">
   <f:layout name="Default" />
   <f:section name="Main">
      <f:format.raw>{content}</f:format.raw>
   </f:section>
   </html>


Recommended Content Element Rendering
-------------------------------------

It's highly recommended to migrate content elements to embrace fluid and
streamline the rendering definitions.


TypoScript Setup
^^^^^^^^^^^^^^^^

.. code-block:: typoscript

   tt_content.simple_content =< lib.fluidContent
   tt_content.simple_content {
      templateName = SimpleContent
      templateRootPaths.12022017 = EXT:my_simple_content/Resources/Private/Templates/
   }


Fluid Template
^^^^^^^^^^^^^^

.. code-block:: html

   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers" data-namespace-typo3-fluid="true">
   <f:layout name="Default" />
   <f:section name="Main">
      <f:format.html>{data.bodytext}</f:format.html>
   </f:section>
   </html>


Customize header rendering
--------------------------

Section in the default layout are optional and have fallbacks. To change
for example the header rendering, place a `Header` section in a template
and place your adjustments. For unsetting the header simply place an empty `Header`
section.

.. code-block:: html

   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers" data-namespace-typo3-fluid="true">
   <f:layout name="Default" />
   <f:section name="Header">
      <h1>{data.header}</h1>
   </f:section>
   <f:section name="Main">
      <f:format.html>{data.bodytext}</f:format.html>
   </f:section>
   </html>


.. index:: Frontend, TypoScript, ext:fluid_styled_content
