.. include:: /Includes.rst.txt

.. _feature-91715:

===================================================================================================
Feature: #91715 - Add multiple has($identifier) methods to \\TYPO3\\CMS\\Core\\Page\\AssetCollector
===================================================================================================

See :issue:`91715`

Description
===========

The new feature can check if the assets such as JavaScript, inline stylesheets,
stylesheets, and media already exist before generating it again.

To accomplish this, new methods has been added to :php:`\TYPO3\CMS\Core\Page\AssetCollector`:

- :php:`hasJavaScript(string $identifier): bool`
- :php:`hasInlineJavaScript(string $identifier): bool`
- :php:`hasStyleSheet(string $identifier): bool`
- :php:`hasInlineStyleSheet(string $identifier): bool`
- :php:`hasMedia(string $identifier): bool`

..  code-block:: php

    //use TYPO3\CMS\Core\Page\AssetCollector;
    //use TYPO3\CMS\Core\Utility\GeneralUtility;

    $assetsCollector = GeneralUtility::makeInstance(AssetCollector::class);
    if ($assetsCollector->hasJavaScript($identifier)) {
      // result: true - javascript with identifier $identifier exists
    } else {
      // result: false - javascript with identifier $identifier do not exists
    }

    // $result<X> is true if $identifier exists, otherwise false.
    $result1 = $assetsCollector->hasJavaScript($identifier);
    $result2 = $assetsCollector->hasInlineJavaScript($identifier);
    $result3 = $assetsCollector->hasStyleSheet($identifier);
    $result4 = $assetsCollector->hasInlineStyleSheet($identifier);
    $result5 = $assetsCollector->hasMedia($identifier);

Impact
======

Users have the ability to check if the asset already exists before regenerating
it, thus avoiding redundancy.

.. index:: PHP-API, ext:core
