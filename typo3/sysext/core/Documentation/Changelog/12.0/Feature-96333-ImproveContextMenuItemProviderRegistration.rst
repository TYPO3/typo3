.. include:: /Includes.rst.txt

.. _feature-96333:

================================================================
Feature: #96333 - Improve ContextMenu item provider registration
================================================================

See :issue:`96333`

Description
===========

The context menu in the TYPO3 backend is used to easily access all
relevant actions for the corresponding record, such as "edit", "hide"
or "delete".

It's furthermore also possible for extensions to extend the context menu
with additional actions using so called "item providers". Those were
previously registered in the global TYPO3 configuration, via the
:php:`ext_localconf.php` file.

Since the introduction of the Symfony service container in TYPO3 v10,
it's possible to autoconfigure services. This feature is now also used
for the context menu item providers. Therefore, the previous registration
step is now superfluous. All item providers are now automatically tagged
and registered based on the implemented
:php:`TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface`.

Impact
======

Custom context menu item providers are now automatically registered, based
on the implemented interface, through the service configuration.

Besides the simplified registration, it's now also possible to use DI
in item provider classes.

.. index:: Backend, PHP-API, ext:backend
