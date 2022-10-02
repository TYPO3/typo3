.. include:: /Includes.rst.txt

.. _deprecation-98371-1663524265:

==============================================
Deprecation: #98371 - Deprecated Fluid getters
==============================================

See :issue:`98371`

Description
===========

Views in a Model-View-Controller (MVC) construct should be data sinks.
Class :php:`\TYPO3\CMS\Fluid\View\StandaloneView` violates this concept by
providing some :php:`getXY()` methods that allow fetching previously set state.

The Fluid class :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext`
is the main object carried around within the rendering chain to keep track
of state. It is the main data object used in view helpers.

As such, :php:`StandaloneView` should not allow fetching state since it allows
to be misused to park state, which is primarily a controller concern instead.

To enforce this pattern, the following methods have been marked as deprecated in
TYPO3 Core v12 and will be removed in TYPO3 v13:

* :php:`\TYPO3\CMS\Fluid\View\StandaloneView->getRequest()`
* :php:`\TYPO3\CMS\Fluid\View\StandaloneView->getFormat()`
* :php:`\TYPO3\CMS\Fluid\View\StandaloneView->getTemplatePathAndFilename()`

Impact
======

Calling one of the above methods triggers a PHP :php:`E_USER_DEPRECATED` level
error.

Affected installations
======================

Instances with extensions using one of the above methods.

Migration
=========

Do not misuse :php:`StandaloneView` as data source. Typically, controllers
should handle and keep track of state like a PSR-7 Request and set or update
view state.

.. index:: Fluid, NotScanned, ext:fluid
