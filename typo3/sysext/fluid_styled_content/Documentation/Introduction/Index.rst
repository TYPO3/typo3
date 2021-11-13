.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

.. _what-does-it-do:

What does it do?
================

*fluid_styled_content* handles the rendering of TYPO3's default content elements
and comes bundled as part of the Core. These content elements are rendered using
the Fluid templating engine.

These content elements can be used as-is and can also be modified depending on your
requirements. You are not bound to using only these content elements. It is possible
to add new content elements to the existing set. This document details how to use,
adapt, enhance and create new content elements.

Optionally *fluid_styled_content* offers basic CSS that takes care of
positioning content according to fields chosen in the backend.

For example, if you create a content element of type
:guilabel:`Text with Images` with a centered headline, a subheader, some text
and an image with the position :guilabel:`in text, right`:

.. include:: /Images/AutomaticScreenshots/Introduction/BackendExample.rst.txt

The output of the HTML is rendered by the Fluid template
:file:`typo3/sysext/fluid_styled_content/Resources/Private/Templates/Textpic.html`
which in turn includes several partials.

See the following, shortened code example::

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers" data-namespace-typo3-fluid="true">
    <f:layout name="Default" />
    <f:section name="Header">
        <f:render partial="Header/All" arguments="{_all}" />
    </f:section>
    <f:section name="Main">

        <div class="ce-textpic ce-{gallery.position.horizontal} ce-{gallery.position.vertical}">
            <f:render partial="Media/Gallery" arguments="{_all}" />

            <f:if condition="{data.bodytext}">
                <div class="ce-bodytext">
                    <f:format.html>{data.bodytext}</f:format.html>
                </div>
            </f:if>
        </div>

    </f:section>
    </html>

The  HTML output then looks like this:

.. code-block:: html

    <div id="c1" class="frame frame-default frame-type-textpic frame-layout-0">
      <header>
        <h2 class="ce-headline-center">
          A centered headline
        </h2>
        <h3 class="ce-headline-center">
          A subheader
        </h3>
      </header>
      <div class="ce-textpic ce-right ce-intext">
        <div class="ce-gallery" data-ce-columns="1" data-ce-images="1">
          <div class="ce-row">
            <div class="ce-column">
              <figure class="image">
              <img class="image-embed-item"
                src="/fileadmin/user_upload/TYPO3.png" width="280" height="280" loading="lazy" alt="">
              </figure>
            </div>
          </div>
        </div>
        <div class="ce-bodytext">
          <p>Lorem ipsum dolor sit...</p>
        </div>
      </div>
    </div>

If the default CSS provided by this extension was also included, the output
could look like the following in the browser:

.. include:: /Images/ManualScreenshots/Introduction/ExampleOutput.rst.txt

.. _history:

A little bit of history
=======================

In early years, TYPO3's content elements were rendered by the static template called
*content (default)*. This was mainly based on font-tags for styling and tables for
positioning which was needed to achieve the visual constructions in old versions of web
browsers.

Some time later the extension *css_styled_content* was introduced, which focused on
reducing the amount of TypoScript and providing XHTML/HTML5 markup which could be styled
by Cascading Style Sheets (CSS), a style sheet language used for describing the look and
formatting of a document written in a markup language. Still this extension was heavily
based on TypoScript and did allow custom modifications up to some point.

Since the introduction of the Fluid templating engine, more websites are using this for
page templating. Newer TYPO3 CMS packages (extensions) are also using Fluid as their base
templating engine. The content elements which were provided with TYPO3 CMS by default were
still using TypoScript and some PHP code.

Since TYPO3 7.5 the default content elements are provided by the extension
*fluid_styled_content* and thus use Fluid as template engine. The main benefit being that
hardly any knowledge of TypoScript is now needed to make changes. Integrators can easily
exchange the base content element Fluid templates with their own. With Fluid, more complex
functionality that exceed the simple output of values has to be implemented with
ViewHelpers. Every ViewHelper has its own PHP class. Several basic ViewHelpers are
provided by Fluid. When using your own Fluid templates, developers can add extra
functionality with their own ViewHelpers.
