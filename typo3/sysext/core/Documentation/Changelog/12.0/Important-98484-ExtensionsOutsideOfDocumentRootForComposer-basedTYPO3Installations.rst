.. include:: /Includes.rst.txt

.. _important-98484-1664553704:

==============================================================================================
Important: #98484 - Extensions outside of document root for Composer-based TYPO3 installations
==============================================================================================

See :issue:`98484`

Description
===========

TYPO3 v12 requires the Composer plugin `typo3/cms-composer-installers` with v5,
which automatically installs extensions into Composer's :file:`vendor/`
directory, just like any other regular dependency.

However, in order to allow linking and rendering assets in the public web folder,
the TYPO3 installation symlinks public folders to a specific location. For more details
and the background about the change, read more:

* https://usetypo3.com/composer-changes-for-typo3-v11-and-v12.html
* https://b13.com/core-insights/typo3-and-composer-weve-come-a-long-way

Please note that this only affects TYPO3 installations in Composer mode.

.. index:: CLI, PHP-API, ext:core
