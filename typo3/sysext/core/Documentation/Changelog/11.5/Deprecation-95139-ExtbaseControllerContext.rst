.. include:: /Includes.rst.txt

===============================================
Deprecation: #95139 - Extbase ControllerContext
===============================================

See :issue:`95139`

Description
===========

The Extbase related class :php:`TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext`
has been used in the past to transfer data between Extbase controllers and Fluid
views. It has been superseded by class :php:`TYPO3\CMS\Fluid\Core\Rendering\RenderingContext`
with various preparation patches. To further decouple Fluid from Extbase, class
:php:`ControllerContext` has been marked as deprecated.


Impact
======

Accessing :php:`ControllerContext` and consuming information carried in it has
been marked as deprecated. The class will be removed in TYPO3 v12. The object is bound
to various Fluid view related classes and all occurrences have been marked with
an :php:`@deprecated` annotation.

To retain backwards compatibility, accessing :php:`ControllerContext` does not
actively trigger a PHP :php:`E_USER_DEPRECATED` error in most cases, though.


Affected Installations
======================

Instances with extensions that access :php:`ControllerContext` are affected. This
typically affects extensions which provide own view-helpers. The extension scanner
should find possible matches.


Migration
=========

Two getters of the class have already been marked as deprecated with previous patches, namely
:php:`->getUriBuilder()` as documented with :php:`->getFlashMessageQueue()`. Classes
should inject instances of these objects instead, or should :php:`makeInstance()` them.

Method :php:`getRequest()` is available in controllers directly, and view-helpers
receive the current request by calling :php:`RenderingContext->getRequest()`.

Method :php:`getArguments()` returns the Extbase :php:`Arguments` created by the
:php:`ActionController`. The getter has become mostly useless within Fluid context
since argument validation of forms is abstracted differently since various core versions.
If that object construct is still needed, it should be transferred differently to
consuming classes, for instance by assigning it as variable to the view and accessing
it in a view-helper using the variable container. In many cases it should be sufficient
to directly work with the request object instead.


.. index:: Fluid, PHP-API, PartiallyScanned, ext:extbase
