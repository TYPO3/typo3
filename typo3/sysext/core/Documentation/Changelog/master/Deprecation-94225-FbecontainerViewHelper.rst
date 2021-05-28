.. include:: ../../Includes.txt

===============================================
Deprecation: #94225 - f:be.container ViewHelper
===============================================

See :issue:`94225`

Description
===========

The :html:`<f:be.container>` view helper has been deprecated.

This backend module related view helper was pretty useless since
it mostly provides the same functionality as :html:`<f:be.pageRenderer>`,
with the additional opportunity to render an empty doc header.


Impact
======

Using the view helper in fluid templates will log a deprecation warning
and the view helper will be dropped with v12.


Affected Installations
======================

The limited functionality of the view helper likely leads to little
usage numbers.
Searching extensions for the string html:`<f:be.container>` should
reveal any usages.


Migration
=========

When this view helper is used to register additional backend module
resources like CSS or JavaScript, :html:`<f:be.pageRenderer>` can be
used as drop-in replacement.

If the view helper is used to additionally render an empty ModuleTemplate,
this part should be moved to a controller instead. Simple example:

.. code-block:: php

    $moduleTemplate->setContent($view->render());
    return new HtmlResponse($moduleTemplate->renderContent());

.. index:: Backend, Fluid, NotScanned, ext:fluid
