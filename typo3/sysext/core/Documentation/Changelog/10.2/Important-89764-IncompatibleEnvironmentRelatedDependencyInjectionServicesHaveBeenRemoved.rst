.. include:: /Includes.rst.txt

====================================================================================================
Important: #89764 - Incompatible environment related dependency injection services have been removed
====================================================================================================

See :issue:`89764`

Description
===========

TYPO3 added support for Symfony 5.0 `symfony/dependency-injection:5` and
therefore had to drop non-object services.

Affected dependency injection services are the following boolean services:

- env.is_unix
- env.is_windows
- env.is_cli
- env.is_composer_mode

The services variables can be substituted by using dynamic Symfony
environment parameters:

- "%env(TYPO3:isUnix)%"
- "%env(TYPO3:isWindows)%"
- "%env(TYPO3:isCli)%"
- "%env(TYPO3:isComposerMode)%"

.. index:: PHP-API, ext:core
