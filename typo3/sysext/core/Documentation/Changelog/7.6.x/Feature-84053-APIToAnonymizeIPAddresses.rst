.. include:: /Includes.rst.txt

===============================================
Feature: #84053 - API to anonymize IP addresses
===============================================

See :issue:`84053`

Description
===========

A new API has been introduced which can be used to anonymize IP addresses.
This shall help to comply with data protection and privacy laws and requirement.

:php:`\TYPO3\CMS\Core\Utility\IpAnonymizationUtility::anonymizeIp(string $ipAddress, int $mask = null)`

If :php:`$mask` is set to null (default value), the setting :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['ipAnonymization']` is taken into account.

The following options for :php:`$mask` are possible:

- `0`: The anonymization is disabled.
- `1`: For IPv4 addresses the last byte is masked. E.g. :code:`192.168.100.10` is transformed to :code:`192.168.100.0`.
       For IPv6 addresses the Interface ID. E.g. :code:`2002:6dcd:8c74:6501:fb2:61c:ac98:6bea` is transformed to :code:`2002:6dcd:8c74:6501::`
- `2`: For IPv4 addresses the last two bytes are masked. E.g. :code:`192.168.100.10` is transformed to :code:`192.168.0.0`.
       For IPv6 addresses the Interface ID and SLA ID. E.g. :code:`2002:6dcd:8c74:6501:fb2:61c:ac98:6bea` is transformed to :code:`2002:6dcd:8c74::`

The default value for :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['ipAnonymization']` is :php:`1`!

Impact
======

The core uses this API whenever IP addresses are stored, this includes:

- Indexed Search uses the new setting for its search statistics.

.. index:: PHP-API, ext:core, ext:indexed_search
