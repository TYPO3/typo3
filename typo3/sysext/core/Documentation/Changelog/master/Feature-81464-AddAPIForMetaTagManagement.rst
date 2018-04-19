.. include:: ../../Includes.txt

=================================================
Feature: #81464 - Add API for meta tag management
=================================================

See :issue:`81464`

Description
===========

In order to have the possibility to set metatags in a flexible (but regulated way), a new Meta Tag API is introduced.

The API uses `MetaTagManagers` to manage the tags for a "family" of meta tags. The core e.g. ships an OpenGraph MetaTagManager that is responsible for all OpenGraph tags. In addition to the MetaTagManagers included in the core, you can also register your own `MetaTagManager` in the `MetaTagManagerRegistry`.

Using the Meta Tag API
======================

To use the API, first get the right `MetaTagManager` for your tag from the `MetaTagManagerRegistry`. You can use that manager to add your meta tag; see the example below for the `og:title` meta tag.

.. code-block:: php

    $metaTagManager = \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry::getInstance()->getManagerForProperty('og:title');
    $metaTagManager->addProperty('og:title', 'This is the OG title from a controller');

This code will result in a `<meta property="og:title" content="This is the OG title from a controller" />` tag in frontend.

If you need to specify sub-properties, e.g. `og:image:width`, you can use the following code:

.. code-block:: php

    $metaTagManager = \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry::getInstance()->getManagerForProperty('og:image');
    $metaTagManager->addProperty('og:image', '/path/to/image.jpg', ['width' => 400, 'height' => 400]);

You can also remove a specific property:

.. code-block:: php

    $metaTagManager = \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry::getInstance()->getManagerForProperty('og:title');
    $metaTagManager->removeProperty('og:title');

Or remove all previously set meta tags of a specific manager:

.. code-block:: php

    $metaTagManager = \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry::getInstance()->getManagerForProperty('og:title');
    $metaTagManager->removeAllProperties();

Creating your own MetaTagManager
================================

If you need to specify the settings and rendering of a specific meta tag (for example when you want to make it possible to have multiple occurences of a specific tag), you can create your own `MetaTagManager`. This MetaTagManager should implement `\TYPO3\CMS\Core\MetaTag\MetaTagManagerInterface`.

To use the manager, you have to register it in `ext_localconf.php`:

.. code-block:: php

    $metaTagManagerRegistry = \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry::getInstance();
    $metaTagManagerRegistry->registerManager(
        'custom',
        \Some\CustomExtension\MetaTag\CustomMetaTagManager::class
    );

Registering a `MetaTagManager` works with the `DependencyOrderingService`. So you can also specify the priority of the manager by setting the third (before) and fourth (after) parameter of the method. If you for example want to implement your own `OpenGraphMetaTagManager`, you can use the following code:

.. code-block:: php

    $metaTagManagerRegistry = \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry::getInstance();
    $metaTagManagerRegistry->registerManager(
        'myOwnOpenGraphManager',
        \Some\CustomExtension\MetaTag\MyOpenGraphMetaTagManager::class,
        ['opengraph']
    );

This will result in `MyOpenGraphMetaTagManager` having a higher priority and it will first check if your own manager can handle the tag before it checks the default manager provided by the core.

TypoScript and PHP
==================

You can set your meta tags by TypoScript and PHP (for example from plugins). First the meta tags from content (plugins) will be handled. After that the meta tags defined in TypoScript will be handled.

It is possible to override earlier set meta tags by TypoScript if you explicitly say this should happen. Therefore the `meta.*.replace` option was introduced. It is a boolean flag with these values:

* `1`: The meta tag set by TypoScript will replace earlier set meta tags
* `0`: (default) If the meta tag is not set before, the meta tag will be created. If it is already set, it will ignore the meta tag set by TypoScript.

.. code-block:: typoscript

    page.meta {
        og:site_name = TYPO3
        og:site_name.attribute = property
        og:site_name.replace = 1
    }

When you set the property replace to 1 at the specific tag, the tag will replace tags that are set from plugins.

Impact
======

By using the new API it is not possible to have duplicate metatags, unless this is explicitly allowed. If you use custom meta tags and want to have multiple occurrences of the same meta tag, you have to create your own `MetaTagManager`.

.. index:: ext:core
