.. include:: /Includes.rst.txt

.. _feature-97188:

=======================================================
Feature: #97188 - New registration for element browsers
=======================================================

See :issue:`97188`

Description
===========

The system extension `backend` provides different `element browsers`,
such as the "File browser" or the "Database browser" to select files
and records in e.g. FormEngine fields. Extension authors are able to
register their own browsers. This was previously done using global
configuration.

However, since all `element browsers` have to implement the
:php:`ElementBrowserInterface`, this fact is now used to automatically
register the `element browsers`, based on the interface, if
:yaml:`autoconfigure` is enabled in :file:`Services.yaml`. Alternatively,
one can manually tag a custom `element browser` with the
:yaml:`recordlist.elementbrowser` tag (see section "Migration" in the
:doc:`breaking changelog <Breaking-97188-RegisterElementBrowsersViaServiceConfiguration>`).

Due to the autoconfiguration, the identifier has to be provided by the
class directly, using the now required :php:`getIdentifier()` method.
When extending :php:`\TYPO3\CMS\Backend\Browser\AbstractElementBrowser`
it's sufficient to set the `$identifier` class property.

Impact
======

`element browsers` are now automatically registered through the service
configuration, based on the implemented interface.

.. index:: Backend, LocalConfiguration, PHP-API, ext:backend
