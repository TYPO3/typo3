.. include:: /Includes.rst.txt

================================================================
Breaking: #88564 - PageTSconfig setting "TSFE.constants" removed
================================================================

See :issue:`88564`

Description
===========

The PageTSconfig / UserTSconfig :typoscript:`TSFE.constants`, which allowed to override settings constants
on a per-tree level page was introduced in TYPO3 at the very beginning, long before TSconfig had conditions.

It was used to share TypoScript-based configuration between frontend / backend, and on a per-page/tree level.

However, this has been superseded for a long time by using proper configuration files which
can be loaded at any time, for example when :file:`ext_localconf.php` of an extension is loaded.

Therefore, the option has been removed.


Impact
======

Setting :typoscript:`TSFE.constants` in PageTSconfig or UserTSconfig has no effect, as it is not evaluated
anymore.


Affected Installations
======================

Any TYPO3 installation using :typoscript:`TSFE.constants` in their PageTSconfig.


Migration
=========

It is recommended to include TypoScript conditions in setup/constants, also since constants+setup
are evaluated in Backend context for Extbase modules. This option is not needed anymore and
can be substituted by simple constants in `sys_template` or any Extension inclusion files as well.

.. index:: TSConfig, NotScanned
