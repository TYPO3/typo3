
.. include:: ../../Includes.txt

=================================================================================
Breaking: #66001 - Third-party libraries installed via composer are now in vendor
=================================================================================

See :issue:`66001`

Description
===========

All composer-installed libraries which the TYPO3 Core uses are now installed under `vendor` (composer default vendor directory)
when running `composer install`. This way the packaging process for releasing TYPO3 CMS as tarball or zip
can trigger a fully working installation without having to ship Packages/ for third-party libraries. Before composer
installed all third-party libraries in the folder `Packages/Libraries`.


Impact
======

Any existing installation that is set up via composer based on the composer.json of the TYPO3.CMS.git repository
will break if Unit Tests or Functional Tests via `bin/phpunit` will fail unless composer dependencies have been
completely rebuilt.


Affected installations
======================

Installations using a Packages/ directory within the typo3_src/ folder structure, most commonly by having checked out
the TYPO3.CMS.git Repository and having run `composer install` after that. Any installations using the common
TYPO3 distribution from composer.typo3.org are not affected.


Migration
=========

Running `rm -rf vendor/ bin/ Packages/Libraries/ composer.lock; composer install` will make PHPunit
work again.


.. index:: PHP-API
