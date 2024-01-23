.. include:: /Includes.rst.txt

.. _deprecation-102895-1706003502:

===================================================================
Deprecation: #102895 - ExtensionManagementUtility::getExtensionIcon
===================================================================

See :issue:`102895`

Description
===========

The PHP method :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionIcon`
has been deprecated in favor of :php:`\TYPO3\CMS\Core\Package\Package->getPackageIcon`.


Impact
======

Calling the method :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionIcon`
will trigger a PHP deprecation warning.


Affected installations
======================

TYPO3 installations with custom extensions calling the method.


Migration
=========

Migrate towards the :php:`PackageManager` implementation, which can be added
via Dependency Injection or retrieved via :php:`GeneralUtility::makeInstance()`.

Before
------

..  code-block:: php

    $iconPathInPackage = ExtensionManagementUtility::getExtensionIcon($extensionKey);
    $fullIconPath = ExtensionManagementUtility::getExtensionIcon($extensionKey, true);

After
-----

..  code-block:: php

    $packageManager = GeneralUtility::makeInstance(PackageManager::class);
    $package = $packageManager->getPackage($extensionKey);
    if ($package->getPackageIcon()) {
        $iconPathInPackage = $package->getPackageIcon();
        $fullIconPath = $package->getPackagePath() . $package->getPackageIcon();
    }

.. index:: PHP-API, FullyScanned, ext:core
