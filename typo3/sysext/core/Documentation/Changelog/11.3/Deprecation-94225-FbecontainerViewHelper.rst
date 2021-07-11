.. include:: ../../Includes.txt

===============================================
Deprecation: #94225 - f:be.container ViewHelper
===============================================

See :issue:`94225`

Description
===========

The :html:`<f:be.container>` ViewHelper has been deprecated.

This backend-module-related ViewHelper was pretty useless since
it mostly provides the same functionality as :html:`<f:be.pageRenderer>`,
with the additional opportunity to render an empty doc header.


Impact
======

Using the ViewHelper in Fluid templates will trigger a PHP
:php:`E_USER_DEPRECATED` error.


Affected Installations
======================

The limited functionality of the ViewHelper likely leads to little
usage numbers.
Searching extensions for the string html:`<f:be.container>` should
reveal any usages.


Migration
=========

When this ViewHelper is used to register additional backend module
resources like CSS or JavaScript, :html:`<f:be.pageRenderer>` can be
used as drop-in replacement.

If the ViewHelper is used to additionally render an empty ModuleTemplate,
this part should be moved to a controller instead. Simple example:

.. code-block:: php

    $moduleTemplate->setContent($view->render());
    return new HtmlResponse($moduleTemplate->renderContent());

.. index:: Backend, Fluid, NotScanned, ext:fluid
