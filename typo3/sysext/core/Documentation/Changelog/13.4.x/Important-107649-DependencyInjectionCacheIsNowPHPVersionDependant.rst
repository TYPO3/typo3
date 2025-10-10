..  include:: /Includes.rst.txt

..  _important-107649-1760090777:

============================================================================
Important: #107649 - Dependency Injection cache is now PHP version dependant
============================================================================

See :issue:`107649`

Description
===========

TYPO3 uses the PHP library `symfony/dependency-injection` to build a dependency
injection container that contains class factories for services used by TYPO3 or
by installed extensions.

With the update to `symfony/dependency-injection` v7.3 – which may be installed
in TYPO3 v13 composer mode – the created factories are optimized to use certain
PHP language level features, if available, which result in a cache that is
incompatible when used with older PHP versions.

In a scenario where the dependency injection cache is created in a CLI PHP
process (e.g. PHP v8.4), this may result in a cache to be created that is
incompatible with a Web PHP process (e.g. PHP v8.2), if the minor versions
of the CLI and Web environments differ.

For this reason the major and minor PHP version numbers are now hashed into the
dependency injection cache identifier, resulting in a possible cache-miss on the
first web-request after a deployment, if the system was prepared via
:bash:`bin/typo3` with a CLI PHP process version that is different to the Web
PHP version.

Make sure to configure the PHP CLI process version :bash:`php -v` to use
the same version number as configured for the Web process. The Web process
version can be introspected in the backend toolbar entry
:guilabel:`System Information > PHP Version`.

..  index:: CLI, ext:core
