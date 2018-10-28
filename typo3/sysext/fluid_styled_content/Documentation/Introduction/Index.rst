.. include:: ../Includes.txt

.. _introduction:

============
Introduction
============

.. _what-does-it-do:

What does it do?
================

The extension "fluid_styled_content" handles the rendering of the default set of content elements
shipped with the core by using the template engine "fluid".

"fluid_styled_content" provides a basic set of content elements which you can use for your
website. These can be used out-of-the-box, but can be adapted to your or your client
needs. You are not bound to using only these content elements. For custom made
functionality it is possible to add extra content elements to the basic set. How to adapt,
enhance or add content elements will be described in this document.

The rendering of the provided set of content elements is based on HTML5. Nowadays most of
the websites are built with this core technology markup language used for
structuring and presenting content in the World Wide Web. If your website is using
another markup, like HTML4 or XHTML, it is easy to exchange the provided templates with
your own.


.. _history:

A little bit of history
=======================

At the beginning of TYPO3 CMS content elements were rendered by the static template called
*content (default)*. This was mainly based on font-tags for styling and tables for
positioning which was needed to achieve the visual constructions in old versions of web
browsers.

Some time later the extension "css_styled_content" was introduced, which focused on
reducing the amount of TypoScript and providing XHTML/HTML5 markup which could be styled
by Cascading Style Sheets (CSS), a style sheet language used for describing the look and
formatting of a document written in a markup language. Still this extension was heavily
based on TypoScript and did allow custom modifications up to some point.

Since the introduction of the templating engine Fluid, more websites are using this for
page templating. Newer TYPO3 CMS packages (extensions) are also using Fluid as their base
templating engine. The content elements which were provided with TYPO3 CMS by default were
still using TypoScript and partly PHP code.

Since TYPO3 CMS version 7.5 the default content elements have been moved to this
extension **fluid_styled_content**, also using Fluid as their templating engine. The benefits are that
hardly any knowledge of TypoScript is needed to make changes. Integrators can easily
exchange the base content element Fluid templates with their own. In Fluid more complex
functionality that exceed the simple output of values has to be implemented with
ViewHelpers. Every ViewHelper has its own PHP class. Several basic ViewHelpers are
provided by Fluid. When using your own Fluid templates, developers can add extra
functionality with their own ViewHelpers, extending the possibilities of the content
elements.


.. _support:

Support
=======

Please see/report problems on TYPO3 Forge
`https://forge.typo3.org/projects/typo3cms-core/issues
<https://forge.typo3.org/projects/typo3cms-core/issues>`_
under category content rendering.

You may get support in the use of this extension by subscribing to
`https://forum.typo3.org/index.php/f/41/ <https://forum.typo3.org/index.php/f/41/>`_ .
