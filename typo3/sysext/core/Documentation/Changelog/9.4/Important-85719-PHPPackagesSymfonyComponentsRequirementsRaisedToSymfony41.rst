.. include:: /Includes.rst.txt

=======================================================================================
Important: #85719 - PHP Packages: Symfony Components requirements raised to Symfony 4.1
=======================================================================================

See :issue:`85719`

Description
===========

Due to the introduction of the PHP requirement of `symfony/routing` with a minimum requirement of
version 4.1, all other Symfony Components have been raised to have at least 4.1 as well.

This includes the following symfony components:

* symfony/finder
* symfony/console
* symfony/yaml
* symfony/expression-language

The package `symfony/routing` is a must-have for TYPO3 for Route Matching,
which has been heavily improved in version 4.1 performance-wise. As TYPO3 should have the best experience
with routing, it is critical to use at least 4.1, and no version lower than that.

If a composer-based TYPO3 installation depends on a package that is not compatible with
symfony components lower than 4.1, it is not possible to upgrade TYPO3 to v9.4 without fixing other
requirements.

In this case, evaluate if other third-party packages can be upgraded to be compatible with
Symfony 4.1 components and require newer versions of these packages, or open up a support ticket
in the respective package project website to ensure compatibility with newer symfony versions.

.. index:: PHP-API
