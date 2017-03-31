.. include:: ../../Includes.txt

============================================================================
Important: #80606 - Testing Framework Removal / Use composer package instead
============================================================================

See :issue:`80606`

Description
===========

The TYPO3 core base testing classes were moved to a separate composer package and removed
from the TYPO3 core. If you want to continue using the testing framework classes please use
composer and require the package typo3/testing-framework.

To make sure you don't run into path issues, you can set the following environment variables:
TYPO3_PATH_WEB = The path to your web root
TYPO3_PATH_PACKAGES = The path to your vendor folder

Find the new package on github_

And on packagist_

.. _github: https://github.com/TYPO3/testing-framework
.. _packagist: https://packagist.org/packages/typo3/testing-framework

.. index:: CLI, PHP-API
