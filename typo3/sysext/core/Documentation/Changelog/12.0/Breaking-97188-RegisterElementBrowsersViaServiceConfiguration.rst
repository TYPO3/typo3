.. include:: /Includes.rst.txt

.. _breaking-97188:

======================================================================
Breaking: #97188 - Register element browsers via service configuration
======================================================================

See :issue:`97188`

Description
===========

The `element browsers` in EXT:backend are now registered via service
configuration, see the :doc:`feature changelog <Feature-97188-NewRegistrationForElementBrowsers>`.
Therefore the registration via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']`
has been removed.

Additionally, to be able to use autoconfiguration, the `element browser`
identifier has to be provided by the service directly using the
:php:`getIdentifier()` method, which is now required by the
:php:`ElementBrowserInterface`.

In case a custom `element browser` extends
:php:`\TYPO3\CMS\Backend\Browser\AbstractElementBrowser`,
only the class property `$identifier` has to be set, e.g.
:php:`protected string $identifier = 'my_browser';`.

Impact
======

Registration of custom `element browsers` via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']`
is not evaluated anymore.

The :php:`ElementBrowserInterface` is extended for
:php:`public function getIdentifier(): string`.

Affected Installations
======================

All TYPO3 installations using the old registration.

All TYPO3 installations with custom `element browsers`, not implementing
:php:`public function getIdentifier()`.

Migration
=========

Remove :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']`
from your :file:`ext_localconf.php` file.

If :yaml:`autoconfigure` is not enabled in your :file:`Configuration/Services.(yaml|php)`,
add the tag :yaml:`recordlist.elementbrowser` manually to your `element browser` service.

..  code-block:: yaml

    Vendor\Extension\Recordlist\MyBrowser:
      tags:
        - name: recordlist.elementbrowser

Additionally, make sure to either implement
:php:`public function getIdentifier(): string` or, in case your `element browser`
extends :php:`AbstractElementBrowser`, to set the `$identifier` class property.

.. index:: Backend, LocalConfiguration, PHP-API, FullyScanned, ext:backend
