.. include:: /Includes.rst.txt

================================================
Feature: #21638 - Introduced IP locking for IPv6
================================================

See :issue:`21638`

Description
===========

The IP-locking functionality has been extended to support IPv6 now as well.
This security feature enables binding of a user session (Backend or Frontend) to an IP address or a part of it.

The available configuration options with their default values for IP-locking are:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['FE']['lockIP'] = 2;
   $GLOBALS['TYPO3_CONF_VARS']['FE']['lockIPv6'] = 2;

   $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'] = 4;
   $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIPv6'] = 2;

The configuration can be changed via the Admin Tools -> Settings menu.
The exact meaning of the numbers used for the configuration are documented there.

Code-wise a separate IpLocker class :php:`\TYPO3\CMS\Core\Authentication\IpLocker` has been added, which takes care of the IP-locking for both IP versions.

.. index:: Backend, Frontend, LocalConfiguration
