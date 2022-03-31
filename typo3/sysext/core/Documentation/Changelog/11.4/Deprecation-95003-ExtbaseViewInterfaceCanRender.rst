.. include:: /Includes.rst.txt

=======================================================
Deprecation: #95003 - Extbase ViewInterface canRender()
=======================================================

See :issue:`95003`

Description
===========

To streamline and simplify Fluid view related classes, the
Extbase related :php:`TYPO3\CMS\Extbase\Mvc\View\ViewInterface`
method :php:`canRender()` has been dropped from the interface.

Impact
======

The method should not be used anymore. Implementations in consuming
view classes are kept in TYPO3 v11 but have been marked as deprecated and
trigger a PHP :php:`E_USER_DEPRECATED` error upon usage.


Affected Installations
======================

Method :php:`canRender()` had limited use within Extbase, it is rather
unlikely many instances with extensions using the method exist. It's
purpose was to check for Fluid template existence before calling
:php:`$view->render()`, but all existing view implementations throw an
exception during :php:`render()` if a template path can't be resolved.


Migration
=========

Do not call :php:`canRender()` on template view instances, but let
:php:`render()` throw :php:`\TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException` on error
instead.

.. index:: PHP-API, NotScanned, ext:extbase
