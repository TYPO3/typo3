.. include:: ../../Includes.txt

=======================================================
Deprecation: #95003 - Extbase ViewInterface canRender()
=======================================================

See :issue:`95003`

Description
===========

To streamline and simplify fluid view related classes, the
extbase related :php:`TYPO3\CMS\Extbase\Mvc\View\ViewInterface`
method :php:`canRender()` has been dropped from the interface.

Impact
======

The method should not be used anymore. Implementations in consuming
view classes are kept in v11 but have been marked as deprecated and
log a deprecation level error upon usage.


Affected Installations
======================

Method :php:`canRender()` had limited use within extbase, it is rather
unlikely many instances with extensions using the method exist. It's
purpose was to check for fluid template existence before calling
:php:`$view->render()`, but all existing view implementations throw an
exception during :php:`render()` if a template path can't be resolved.


Migration
=========

Do not call :php:`canRender()` on template view instances, but let
:php:`render()` throw :php:`InvalidTemplateResourceException` on error
instead.

.. index:: PHP-API, NotScanned, ext:extbase
