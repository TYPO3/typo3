.. include:: /Includes.rst.txt

.. _deprecation-95222:

===========================================
Deprecation: #95222 - Extbase ViewInterface
===========================================

See :issue:`95222`

Description
===========

To further streamline Fluid view-related class inheritance and dependencies,
the interface :php:`TYPO3\CMS\Extbase\Mvc\View\ViewInterface` has been marked
as deprecated and will be removed in TYPO3 v12.

Impact
======

This deprecation has minimal impact on TYPO3 v11:

*   The interface remains available in the Core without triggering
    a E_USER_DEPRECATED warning.
*   ViewInterface primarily differs from other view-related classes by
    requiring an implementation of :php:`initializeView()`, a method that was
    never actively used within TYPO3's Core. This method should not be confused
    with :php:`initializeView()` in Extbase controllers, which is frequently
    implemented by developers and serves a different purpose. The removal
    of :php:`initializeView()` only affects view-related logic and does not
    impact controller initialization.
*   Another deviation is the method :php:`setControllerContext()`, which is
    also deprecated because :php:`ControllerContext` itself is marked
    as deprecated.

Affected Installations
======================

The extension scanner will detect usages of Extbase :php:`ViewInterface` as a
strong match.

Migration
=========

Adjusting initializeView() method signature in controllers
----------------------------------------------------------

Some extensions may rely on :php:`ViewInterface` type hints, particularly in
the :php:`initializeView()` method of Extbase action controllers. The default
implementation of :php:`initializeView()` in :php:`ActionController` is empty.

In TYPO3 v12:

*   This empty method will be removed from :php:`ActionController`.
*   However, if an :php:`initializeView()` method exists in a subclass of
    :php:`ActionController`, it will still be called.
*   Extension authors should not call :php:`parent::initializeView($view)`, as
    this parent method will no longer exist.
*   The method signature should be updated to prevent PHP
    contravariance violations:

Old:

..  code-block:: php

    protected function initializeView(ViewInterface $view)

New:

..  code-block:: php

    protected function initializeView($view)

Replacing ViewInterface
-----------------------

Instead of using :php:`\TYPO3\CMS\Extbase\Mvc\View\ViewInterface`, extension
authors should switch to:

*   :php:`\TYPO3\CMS\Fluid\View\StandaloneView` — typically in
    non-Extbase-related classes.
*   :php:`\TYPO3Fluid\Fluid\View\ViewInterface` — for a more
    generic replacement.

Handling Custom Views
---------------------

If an extension defines a custom view implementing :php:`ViewInterface`, note
that auto-configuration based on this interface will be removed in TYPO3 v12.
As a result, manual service configuration in :file:`Services.yaml` may
be necessary.

..  index:: Fluid, PHP-API, FullyScanned, ext:fluid
