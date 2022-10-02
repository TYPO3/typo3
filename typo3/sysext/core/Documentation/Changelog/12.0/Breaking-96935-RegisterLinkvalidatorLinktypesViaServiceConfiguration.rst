.. include:: /Includes.rst.txt

.. _breaking-96935:

=============================================================================
Breaking: #96935 - Register linkvalidator linktypes via service configuration
=============================================================================

See :issue:`96935`

Description
===========

Linkvalidator `linktypes` are now registered via service configuration, also see
:doc:`feature changelog <Feature-96935-NewRegistrationForLinkvalidatorLinktype>`.
Therefore the registration via
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']`
has been removed.

Additionally, to be able to use autoconfiguration, the `linktype` identifier
has to be provided by the service directly using the :php:`getIdentifier()`
method, which is now required by the :php:`LinktypeInterface`.

In case a custom `linktype` extends
:php:`\TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype`,
only the class property `$identifier` has to be set, e.g.
:php:`protected string $identifier = 'my_linktype';`.

Impact
======

Registration of custom `linktypes` via
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']`
is not evaluated anymore.

The :php:`LinktypeInterface` is extended for
:php:`public function getIdentifier(): string`.

Affected Installations
======================

All TYPO3 installations using the old registration.

All TYPO3 installations with custom `linktypes`, not implementing
:php:`public function getIdentifier(): string`.

Migration
=========

Remove :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']`
from your :file:`ext_localconf.php` file.

If :yaml:`autoconfigure` is not enabled in your :file:`Configuration/Services.(yaml|php)`,
add the tag :yaml:`linkvalidator.linktype` manually to your `linktype` service.

..  code-block:: yaml

    Vendor\Extension\Linktype\MyCustomLinktype:
      tags:
        - name: linkvalidator.linktype

Additionally, make sure to either implement
:php:`public function getIdentifier(): string` or, in case your `linktype` extends
:php:`AbstractLinktype`, to set the `$identifier` class property.

.. index:: Backend, LocalConfiguration, PHP-API, FullyScanned, ext:linkvalidator
