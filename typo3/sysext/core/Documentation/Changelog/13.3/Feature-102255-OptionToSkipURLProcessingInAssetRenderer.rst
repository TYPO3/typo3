.. include:: /Includes.rst.txt

.. _feature-102255-1726090749:

=================================================================
Feature: #102255 - Option to skip URL processing in AssetRenderer
=================================================================

See :issue:`102255`

Description
===========

The :php:`\TYPO3\CMS\Core\Page\AssetCollector` options have been extended to
include an `external`
flag. When set for asset files using :php:`$assetCollector->addStyleSheet()`
or :php:`$assetCollector->addJavaScript()`, all processing of the asset
URI (like the addition of the cache busting parameter) is skipped and the input
path will be used as-is in the resulting HTML tag.

Example
=======

The following code skips the cache busting parameter `?1726090820` for the
supplied CSS file:

..  code-block:: php

    $assetCollector->addStyleSheet(
        'myCssFile',
        PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName('EXT:my_extension/Resources/Public/MyFile.css')),
        [],
        ['external' => true]
    );


Resulting in the following HTML output:

..  code-block:: html

    <link rel="stylesheet" href="/_assets/<hash>/myFile.css" />


Impact
======

Developers can now use the :php-short:`\TYPO3\CMS\Core\Page\AssetCollector`
API to embed JavaScript or CSS files without any processing of the
supplied asset URI.

.. index:: PHP-API, ext:core
