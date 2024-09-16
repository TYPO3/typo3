.. include:: /Includes.rst.txt

.. _deprecation-104773-1724940753:

=================================================================
Deprecation: #104773 - ext:backend LoginProviderInterface changes
=================================================================

See :issue:`104773`

Description
===========

Method :php:`\TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface->render()` has been marked as deprecated
and is substituted by  :php:`LoginProviderInterface->modifyView()` that will
be added to the interface in TYPO3 v14, removing :php:`render()` from the
interface in v14.

Related to this, event :php:`\TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent`
has been changed to deprecate :php:`getController()` and :php:`getPageRenderer()`,
while :php:`getRequest()` has been added. :php:`getView()` now typically returns
an instance of :php:`ViewInterface`.

This change is related to the general :ref:`View refactoring <feature-104773-1724939348>`.


Impact
======

The default :php-short:`\TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface`
implementation is
:php-short:`\TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider`
provided by ext:core. This consumer has been adapted.

Using :php:`LoginProviderInterface->render()` in TYPO3 v13 will trigger a
deprecation level log entry and will fail in v14.


Affected installations
======================

Instances with custom login providers that change the TYPO3 backend login
field rendering may be affected. The extension scanner is not configured to
find usages, since method name :php:`render()` is too common. A deprecation
level log message is triggered upon use of the old method.


Migration
=========

Consumers of :php-short:`\TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface`
should implement :php:`modifyView()`
instead, the transition should be smooth. Consumers that need the
:php-short:`\TYPO3\CMS\Core\Page\PageRenderer`
for JavaScript magic, should use :ref:`dependency injection <t3coreapi:Dependency-Injection>`
to receive an instance
of it.

Consumers of :php-short:`\TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent`
should use the request instead, and/or should get an instance of
:php-short:`\TYPO3\CMS\Core\Page\PageRenderer` injected as well.

.. index:: PHP-API, NotScanned, ext:backend
