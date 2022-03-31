.. include:: /Includes.rst.txt

====================================================================
Breaking: #82368 - Signal 'afterExtensionConfigurationWrite' removed
====================================================================

See :issue:`82368`

Description
===========

The extension manager no longer emits signal :php:`afterExtensionConfigurationWrite`.
The code has been moved to the install tool which does not load signal / slot
information at this point.


Impact
======

Slots of this signal are no longer executed.


Affected Installations
======================

Extensions that use signal :php:`afterExtensionConfigurationWrite`. This is a rather seldom
used signal, relevant mostly only for distributions.


Migration
=========

In many cases it should be possible to use signal :php:`afterExtensionInstall` of class
:php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility` instead which is fired after an extension
has been installed.

.. index:: Backend, LocalConfiguration, PHP-API, NotScanned, ext:extensionmanager
