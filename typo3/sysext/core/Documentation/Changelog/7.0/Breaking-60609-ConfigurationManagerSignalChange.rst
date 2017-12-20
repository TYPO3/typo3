
.. include:: ../../Includes.txt

=======================================================
Breaking: #60609 - Configuration Manager Signal Changed
=======================================================

See :issue:`60609`

Description
===========

The extension for which the configuration was written was added to the signal emitted
in the ConfigurationManager of the ExtensionManager as first parameter as the whole signal was
unusable without this information.


Impact
======

The arguments for a method listening to this signal have changed.


Affected installations
======================

A TYPO3 instance is affected if there is code using the signal "afterExtensionConfigurationWrite".

Migration
=========

Rewrite the listening function to use the extension key as first parameter.


.. index:: PHP-API, Backend, ext:extensionmanager
