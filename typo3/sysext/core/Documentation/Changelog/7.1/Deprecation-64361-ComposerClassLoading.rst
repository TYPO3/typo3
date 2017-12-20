
.. include:: ../../Includes.txt

============================================
Deprecation: #64361 - Composer Class Loading
============================================

See :issue:`64361`

Description
===========

TYPO3 CMS started integrating composer support and by that embracing PHP standards PSR-0 and PSR-4 for class
loading that comes with composer.
The old class loader is still present and registered and will handle loading extension classes
that do not follow the above mentioned standards.

For the time being a pre-compiled composer class loader is shipped with the git repository,
so that people using the TYPO3 sources directly from there can use it without requiring a `composer install`
step.

It is possible however to do a `composer install` in the TYPO3 sources directory or a top level distribution directory
to gain full control over class loading of your TYPO3 installation. This step is optional and currently requires
thorough knowledge of composer and as such currently is only recommended for people familiar with this process.

Further technical details can be found in the `wiki`_.

Impact
======

Third party libraries added to a TYPO3 installation via composer can immediately be used without
further manual intervention. The classes cache for most core classes will be gone. Resolving classes
is slowly shifted from a run time task - executed and monitored in every request - to an installation task
with composer.

Affected installations
======================

Some installations could be affected which were previously installed via composer,
but now not properly updated using composer. And outdated Packages/Libraries/autoload.php file
present in the system will lead to fatal errors.


Migration
=========

If you previously installed TYPO3 via composer, make sure you perform a `composer update` command to reflect
the current changes in your Packages folder.


.. _`wiki`: http://wiki.typo3.org/ComposerClassLoader


.. index:: PHP-API
