.. include:: /Includes.rst.txt

============================================================================
Feature: #79124 - Allow overwriting of template paths in BackendTemplateView
============================================================================

See :issue:`79124`

Description
===========

BackendTemplateView now allows overwriting of template paths to add your own locations for templates, partials and layouts in a BackendTemplateView based backend module.


Impact
======

You can now do for example

.. code-block:: php

   $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
   $viewConfiguration = [
      'view' => [
         'templateRootPaths' => ['EXT:myext/Resources/Private/Backend/Templates'],
         'partialRootPaths' => ['EXT:myext/Resources/Private/Backend/Partials'],
         'layoutRootPaths' => ['EXT:myext/Resources/Private/Backend/Layouts'],
      ],
   ];
   $this->configurationManager->setConfiguration(array_merge($frameworkConfiguration, $viewConfiguration));


.. index:: Backend, PHP-API
