.. include:: ../../Includes.txt

===========================================
Deprecation: #95222 - Extbase ViewInterface
===========================================

See :issue:`95222`

Description
===========

To further streamline Fluid view related class inheritance and dependencies,
the interface :php:`TYPO3\CMS\Extbase\Mvc\View\ViewInterface` has been marked
as deprecated and will be removed in v12.


Impact
======

This deprecation has little impact on TYPO3 v11: The interface is kept and still
carried around in the core, no deprecation level log entry is raised.

The interface itself deviates from the casual view related classes only by requiring
an implementation of method :php:`initializeView()` which was never actively used,
calling that method will vanish in v12. The second deviation is method
:php:`setControllerContext()`, and class :php:`ControllerContext` is deprecated, too.


Affected Installations
======================

The extension scanner will find usages of extbase :php:`ViewInterface` as a strong match.


Migration
=========

Some instances may rely on extensions that type hint :php:`TYPO3\CMS\Extbase\Mvc\View\ViewInterface`,
especially in the extbase action controller method :php:`initializeView()`. The default
implementation of that method in :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController` is
empty. To simplify compatibility for extensions supporting both v11 and v12,
that empty method will be removed in v12, but will still be called if it exists in classes
extending :php:`ActionController`.
Extension authors should thus avoid calling :php:`parent::initializeView($view)` in their
implementation of :php:`initializeView()` to prepare towards v12.

Apart from that, usages of :php:`TYPO3\CMS\Extbase\Mvc\View\ViewInterface` should be changed
to the more specific :php:`TYPO3\CMS\Fluid\View\StandaloneView` - usually in non-extbase
related classes, or to the less specific :php:`TYPO3Fluid\Fluid\View\ViewInterface`.

If using a custom view implementing :php:`TYPO3\CMS\Extbase\Mvc\View\ViewInterface`,
keep in mind that auto configuration based on the interface will be dropped in v12, you
may have to configure the service in :file:`Services.yaml` manually.


.. index:: Fluid, PHP-API, FullyScanned, ext:fluid
