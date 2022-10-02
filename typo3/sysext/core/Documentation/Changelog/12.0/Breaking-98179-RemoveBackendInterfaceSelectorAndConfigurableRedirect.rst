.. include:: /Includes.rst.txt

.. _breaking-98179-1660903844:

==============================================================================
Breaking: #98179 - Remove backend interface selector and configurable redirect
==============================================================================

See :issue:`98179`

Description
===========

Previous TYPO3 installations allowed to configure an interface selector in the
backend login that gave the authenticating backend user the possibility to
choose whether to get redirected to frontend or backend by configuring
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] = 'backend,frontend'`.

If only one option was configured, the redirect to either backend or frontend
was enforced, where `backend` was the default configuration.

This feature was meaningful once TYPO3 shipped EXT:feedit, but was conceptually
broken ever since, as the matter of fact a TYPO3 installation can contain
multiple site roots was overseen and a user may get redirected to the wrong
frontend. Also, if EXT:adminpanel is not installed, there is no one-click
solution to access the TYPO3 backend.

Impact
======

The configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces']`
is removed, therefore an authenticated user always gets redirected to the
backend.

Affected installations
======================

All TYPO3 installations relying on this feature are affected.

Migration
=========

The extension scanner will find remaining usages of
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces']`, which can be removed.

If a TYPO3 project really relies on this feature, create an XCLASS of
:php:`\TYPO3\CMS\Backend\Controller\LoginController`, where also a custom Fluid
template may be used.

.. note::

    XCLASSes are not covered by our platform stability promise and may break
    anytime without preliminary information!

.. index:: Backend, FullyScanned, ext:backend
