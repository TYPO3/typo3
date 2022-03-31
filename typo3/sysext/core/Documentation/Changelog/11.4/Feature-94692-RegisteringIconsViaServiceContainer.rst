.. include:: /Includes.rst.txt

=========================================================
Feature: #94692 - Registering Icons via Service Container
=========================================================

See :issue:`94692`

Description
===========

Extensions can now register their custom icons via
a configuration file placed in :file:`Configuration/Icons.php` of their
extension directory, e.g. :file:`typo3conf/ext/my_extension/Configuration/Icons.php`.

Each file needs to return a flat PHP configuration array, with
custom options used for the IconRegistry to register a new icon.

Example:

.. code-block:: php

    <?php
    return [
        'myicon' => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => 'EXT:my_extension/Resources/Public/Icons/myicon.svg'
        ],
        'anothericon' => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => 'EXT:my_extension/Resources/Public/Icons/anothericon.svg'
        ],
        ...
    ];


Impact
======

Using the new approach improves the loading speed of every request
as the registration can be handled at once and cached
during warmup of the core caches.

In addition, extension authors' :file:`ext_localconf.php` files are
drastically reduced, as extension authors have a better overview
and a better separation of concerns when registering custom
functionality.

.. index:: PHP-API, ext:core
