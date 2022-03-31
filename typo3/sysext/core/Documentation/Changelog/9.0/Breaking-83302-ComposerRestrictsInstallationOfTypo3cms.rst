.. include:: /Includes.rst.txt

===============================================================
Breaking: #83302 - Composer restricts installation of typo3/cms
===============================================================

See :issue:`83302`

Description
===========

When running a composer-based TYPO3 instance, it is not possible anymore to require the whole
TYPO3 Core via `composer require typo3/cms`. This package is solely used for Core-development purposes
from now on.

Instead, all system extensions maintained by the TYPO3 Core Team must be required individually.

Some examples:

* `composer require typo3/cms-core:^9`
* `composer require typo3/cms-fluid-styled-content:^9`
* `composer require typo3/cms-extbase:^9`
* `composer require typo3/cms-workspaces:^9`
* `composer require typo3/cms-sys-note:^9`

For convenience, TYPO3 projects can simply require `composer require typo3/minimal` to get the main
system extensions that are needed for a running TYPO3 instance, and add custom system extensions
as mentioned above.


Impact
======

Installing or updating the composer package `typo3/cms` will show an error for TYPO3 v9.


Affected Installations
======================

Composer-based TYPO3 installations that previously required `typo3/cms` in the projects'
`composer.json` file or in any required extension `composer.json` file.


Migration
=========

Extension authors should specifically define their dependencies of system extensions in their
`composer.json` file, if they have previously added `typo3/cms`.

Site administrators / developers should require only the necessary `typo3/cms-*` packages that they
have installed in their projects. In order to find out, which system extensions have been installed,
have a look at `typo3conf/PackageStates.php` and look for all extensions that are located under
`typo3/sysext/`.

.. index:: CLI, NotScanned
