.. include:: ../../Includes.txt

===================================
Feature: #82014 - Extension scanner
===================================

See :issue:`82014`

Description
===========

A new feature in the install tool called "Extension Scanner" finds possible
code lines in extensions that use TYPO3 core API which has been changed.

Currently, PHP core API changes are supported and the extension scanner
does its best to find for instance a usage of a core class which has been
deprecated.

The scanner configuration is maintained by the core team to keep the system
up to date if a patch with a core API change is merged.

The extension scanner is meant as a developer tool to find code places which
may need adaption to raise extension compatibility with younger core versions.

Be aware the scanner is only a helper, not everything is found and it will also
show false positives.

More details about this feature can be found
at `<https://docs.typo3.org/typo3cms/CoreApiReference/ApiOverview/ExtensionScanner/Index.html>`__.

.. index:: Backend, PHP-API
