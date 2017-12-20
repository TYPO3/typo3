.. include:: ../../Includes.txt

=============================================================
Feature: #79622 - New default layout for Fluid Styled Content
=============================================================

See :issue:`79622`

Description
===========

Previously there have been three layouts you could choose from when you were
defining your own custom content elements or overriding an existing template.

To provide a better maintainability and ease of use of overrides we are reducing
these to a single layout that is named `Default` with all sections optional
and fallbacks if the section is not set. Also we are introducing the "DropIn"
concept.


Structure
---------

The `Default` layout consists of five predefined sections that can be utilized to
shape the output for your content rendering. In most cases you will not have
to care about other section than `Main`. The sections will be rendered in that
exact ordering.

- Before
- Header
- Main
- Footer
- After

.. code-block:: html

   <f:spaceless>
      <f:if condition="{data.frame_class} != none">
         <f:then>
            <div id="c{data.uid}" class="frame frame-{data.frame_class} frame-type-{data.CType} frame-layout-{data.layout}{f:if(condition: data.space_before_class, then: ' frame-space-before-{data.space_before_class}')}{f:if(condition: data.space_after_class, then: ' frame-space-after-{data.space_after_class}')}">
               <f:if condition="{data._LOCALIZED_UID}">
                  <a id="c{data._LOCALIZED_UID}"></a>
               </f:if>
               <f:render section="Before" optional="true">
                  <f:render partial="DropIn/Before/All" arguments="{_all}" />
               </f:render>
               <f:render section="Header" optional="true">
                  <f:render partial="Header/All" arguments="{_all}" />
               </f:render>
               <f:render section="Main" optional="true" />
               <f:render section="Footer" optional="true">
                  <f:render partial="Footer/All" arguments="{_all}" />
               </f:render>
               <f:render section="After" optional="true">
                  <f:render partial="DropIn/After/All" arguments="{_all}" />
               </f:render>
            </div>
         </f:then>
         <f:else>
            <a id="c{data.uid}"></a>
            <f:if condition="{data._LOCALIZED_UID}">
               <a id="c{data._LOCALIZED_UID}"></a>
            </f:if>
            <f:if condition="{data.space_before_class}">
               <div class="frame-space-before-{data.space_before_class}"></div>
            </f:if>
            <f:render section="Before" optional="true">
               <f:render partial="DropIn/Before/All" arguments="{_all}" />
            </f:render>
            <f:render section="Header" optional="true">
               <f:render partial="Header/All" arguments="{_all}" />
            </f:render>
            <f:render section="Main" optional="true" />
            <f:render section="Footer" optional="true">
               <f:render partial="Footer/All" arguments="{_all}" />
            </f:render>
            <f:render section="After" optional="true">
               <f:render partial="DropIn/After/All" arguments="{_all}" />
            </f:render>
            <f:if condition="{data.space_after_class}">
               <div class="frame-space-after-{data.space_after_class}"></div>
            </f:if>
         </f:else>
      </f:if>
   </f:spaceless>


DropIn
------

The sections `Before` and `After` are so called "DropIn" sections. DropIns
have been introduced to be able to place additional functionality to all
content elements without overriding layouts or templates. DropIns are
basically placeholders/empty partials that are meant to be overridden if necessary.

DropIn Locations:

- Resources/Private/Partials/DropIn/Before/All.html
- Resources/Private/Partials/DropIn/After/All.html


Handling Optional Sections
--------------------------

Since all sections are optional you do not need to reference them in your
templates. All sections except the `Main` section have a fallback to a default
behaviour if they are not set in the template. This is for example used to render
the content element header.

.. code-block:: html

   <f:render section="Header" optional="true">
      <f:render partial="Header/All" arguments="{_all}" />
   </f:render>

In some cases it can be useful to disable or override the default fallback of a
section. For example if the HTML does not want to render the header at all.

.. code-block:: html

   <f:layout name="Default" />
   <f:section name="Header" />
   <f:section name="Main">
      <f:format.raw>{data.bodytext}</f:format.raw>
   </f:section>

.. code-block:: html

   <f:layout name="Default" />
   <f:section name="Header">
      <f:if condition="{gallery.position.noWrap} != 1">
         <f:render partial="Header/All" arguments="{_all}" />
      </f:if>
   </f:section>
   <f:section name="Main">
      ...
   </f:section>


Basic Usage
-----------

.. code-block:: html

   <f:layout name="Default" />
   <f:section name="Main">
      ...
   </f:section>


.. index:: Fluid, Frontend, ext:fluid_styled_content, TypoScript
