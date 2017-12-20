.. include:: ../../Includes.txt

=======================================================================
Feature: #78842 - Let FLUIDTEMPLATE mimic an actual extbase web request
=======================================================================

See :issue:`78842`

Description
===========

Adds the possibility to let the FLUIDTEMPLATE content element mimic an
actual extbase web request.
This makes it possible to access submitted data like in extbase with
`->controllerContext->getRequest()->getArguments()`


Impact
======

Data which was submitted through a `FLUIDTEMPLATE` content element are now
available within

.. code-block:: php

   $view->getRenderingContext()
        ->getControllerContext()
        ->getRequest()
        ->getArguments()


Affected Installations
======================

Any installation which use the `FLUIDTEMPLATE` content element which are
initialized with the following settings:

.. code-block:: typoscript

   extbase.pluginName
   extbase.controllerExtensionName
   extbase.controllerName
   extbase.controllerActionName

.. index:: Frontend, TypoScript, PHP-API
