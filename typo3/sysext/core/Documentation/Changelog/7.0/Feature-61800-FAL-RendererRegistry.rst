
.. include:: ../../Includes.txt

============================================================
Feature: #61800 - Registry for adding file rendering classes
============================================================

See :issue:`61800`

Description
===========

To be able to render all kinds of media files a file rendering registry is needed where you can register
a "renderer" class that can generate the needed HTML output.

Every renderer has a priority between 1 and 100, 100 is more important than 1.
When the rendererRegistry is asked for a renderer that fits a given file all registered renderer classes are "asked",
in order of priority, if they are able to render the file. The first renderer class that can render the file an
instance is returned by the rendererRegistry.

Every registered renderer class needs to implement the FileRendererInterface. This makes sure the class has a
getPriority(), canRender() and render() method.

- getPriority() returns integer between 1 and 100
- canRender() gets a file(Reference) object as parameter and returns TRUE if the class is able to render the file
  It checks on mime-type but also storage type etc. can be performed to determine if creating the correct output
  is possible
- render() also gets the file(Reference) object as parameter together with width, height and an optional options array
  the return value is the HTML output

A AudioTagRenderer and VideoTagRenderer have already been added.

It is possible to register your own renderer classes in the ext_localconf.php of an extension.

Example:

.. code-block:: php

    $rendererRegistry = \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::getInstance();
    $rendererRegistry->registerRendererClass(
        'MyCompany\\MySpecialMediaFile\\Rendering\\MySpecialMediaFileRenderer'
    );


Impact
======

The registry on its own doesn't do anything. Some followup patches are needed to use this registry
to find the correct renderer class for rendering videos and other media files in BE preview and FE.


.. index:: PHP-API, FAL, Backend
