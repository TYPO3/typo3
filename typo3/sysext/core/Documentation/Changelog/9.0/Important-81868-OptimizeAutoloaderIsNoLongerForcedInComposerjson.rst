.. include:: /Includes.rst.txt

==============================================================================
Important: #81868 - "Optimize autoloader" is no longer forced in composer.json
==============================================================================

See :issue:`81868`

Description
===========

The option "optimize autoloader" (in config section of TYPO3's own composer.json) forced composer to create
optimized autoloader files.

This improves speed but had three disadvantages:

- creating optimized autoloader may take much longer

- new namespaces (folders) during development require an additional :bash:`composer dump`

- no possibility existing to deactivate optimized autoloader
  from CLI, once it's set in composer.json config section

The option was removed from TYPO3's own composer.json, so deployment servers using that need to call
composer install with the -o flag, in order to enable the optimized autoloader.

This change only affects users that are using a non-composer installation but doing a composer install
in TYPO3's root folder - if you are using your own composer.json you won't be affected.

.. index:: CLI, PHP-API
