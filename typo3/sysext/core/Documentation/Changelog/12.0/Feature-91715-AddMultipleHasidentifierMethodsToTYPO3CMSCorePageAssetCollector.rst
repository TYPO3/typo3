.. include:: /Includes.rst.txt

==============================================================================================
Feature: #91715 - Add multiple has($identifier) methods to \TYPO3\CMS\Core\Page\AssetCollector
==============================================================================================

See :issue:`91715`

Description
===========

The new feature can check if the assets such as javascript, inline style sheets,
style sheets, and media already exist before generating it again.

To accomplish this, new methods has been added to :php:`\TYPO3\CMS\Core\Page\AssetCollector`:

- :php:`hasJavaScript(string $identifier): bool`
- :php:`hasInlineJavaScript(string $identifier): bool`
- :php:`hasStyleSheet(string $identifier): bool`
- :php:`hasInlineStyleSheet(string $identifier): bool`
- :php:`hasMedia(string $identifier): bool`

.. code-block:: php

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

Users get the option to check if the asset exists before generating it again,
hence avoiding redundancy.

.. index:: PHP-API, ext:core
