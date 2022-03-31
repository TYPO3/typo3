.. include:: /Includes.rst.txt

============================================================================
Important: #80606 - Testing Framework Removal / Use composer package instead
============================================================================

See :issue:`80606`

Description
===========

The TYPO3 core base testing classes were moved to a separate composer package and removed
from the TYPO3 core. If you want to continue using the testing framework classes please use
composer and require the package `typo3/testing-framework <packagist>`_.

To make sure you don't run into path issues, you can set the following environment variable:
:php:`TYPO3_PATH_ROOT =` The path to your TYPO3 root directory

Find the new package on github_ and on packagist_.

.. _github: https://github.com/TYPO3/testing-framework
.. _packagist: https://packagist.org/packages/typo3/testing-framework

.. index:: CLI, PHP-API
