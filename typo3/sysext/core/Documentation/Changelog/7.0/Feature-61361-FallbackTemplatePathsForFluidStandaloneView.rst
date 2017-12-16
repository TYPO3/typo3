
.. include:: ../../Includes.txt

===================================================================================
Feature: #61361 - Template Path Fallback for Fluid StandaloneView and FLUIDTEMPLATE
===================================================================================

See :issue:`61361`

Description
===========

StandaloneView
--------------

Earlier in the development of Fluid, a template fallback was introduced
in the TemplateView, providing the possibility to pass a set of possible
file locations to the View Configuration, where Templates, Layouts and Partials
can be found.

The same functionality is now available in the StandaloneView. It is possible to
let the system look up the fitting paths for Partials and Layouts. It is
in the nature of the StandaloneView to get a specific template file set, so
for Templates there is no lookup requirement.

As a developer or integrator, you can configure your View as follows:

.. code-block:: php

    $view = $this->objectManager->get(\TYPO3\CMS\Fluid\View\StandaloneView::class);
    $view->setFormat('html');
    $view->setTemplatePathAndFileName(ExtensionManagementUtility::extPath('myExt') . 'Resources/Private/Templates/Email.html');
    $view->setLayoutRootPaths(array(
      'default' => ExtensionManagementUtility::extPath('myExt') . 'Resources/Private/Layouts',
      'specific' => ExtensionManagementUtility::extPath('myTemplateExt') . 'Resources/Private/Layouts/MyExt',
    ));
    $view->setPartialRootPaths(array(
      'default' => ExtensionManagementUtility::extPath('myExt') . 'Resources/Private/Partials',
      'specific' => ExtensionManagementUtility::extPath('myTemplateExt') . 'Resources/Private/Layouts/MyExt',
      'evenMoreSpecific' => 'fileAdmin/templates/myExt/Partials',
    ));


With this, the View will first look up the requested layout file in the path with the key
:code:`specific`, and in case there is no such file, it will fall back to :code:`default`. For the partials the
sequence would be :code:`evenMoreSpecific`, then :code:`specific`, then fall back to :code:`default`.

You are free in the naming
of the keys. The paths are searched from bottom to top.
In case you choose for numeric array keys, the array is ordered first, then reversed for the lookup, so
the highest index is accessed first.

FLUIDTEMPLATE
-------------

Additionally the TypoScript Content Object FLUIDTEMPLATE, which is based on StandaloneView, also supports this
kind of fallback mechanism.
Two new TypoScript options are added for this purpose:

- partialRootPaths
- layoutRootPaths

Example usage:

.. code-block:: typoscript

    page.10 = FLUIDTEMPLATE
    page.10.file = EXT:sitedesign/Resources/Private/Templates/Main.html
    page.10.partialRootPaths {
      10 = EXT:sitedesign/Resources/Private/Partials
      20 = EXT:sitemodification/Resources/Private/Partials
    }


In case you're using the old options (partialRootPath, layoutRootPath) together with the new options, the content of
the old options will be placed at the first position (index zero) in the fallback list.


Impact
======

In order to change the skin of an extension output, provided by the Fluid StandaloneView, you are no longer required to
copy the whole Resources folder into fileadmin or to some specific location, but you can pick only the files you want
to change. Those need to be organized in folders, which are then configured for the view. The system will fall through
all the provided locations, taking the first fitting file it finds.
