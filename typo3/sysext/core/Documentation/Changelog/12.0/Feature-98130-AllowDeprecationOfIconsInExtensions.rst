.. include:: /Includes.rst.txt

.. _feature-98130-1660295017:

==========================================================
Feature: #98130 - Allow deprecation of icons in extensions
==========================================================

See :issue:`98130`

Description
===========

Extension authors are now able to deprecate icons if they are meant to be public
API. A new option :php:`deprecated` is introduced that may contain the following
data:

* :php:`since` - since when is the icon deprecated
* :php:`until` - when will the icon be removed
* :php:`replacement` - if given, an alternative icon is offered

Impact
======

An extension that provides icons for broader use is now able to mark such icons
as deprecated properly with logging to the TYPO3 deprecation log.

Example:

..  code-block:: php

    // Configuration/Icons.php
    return [
        'deprecated-icon' => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            'source' => 'EXT:my_extension/Resources/Public/Icons/deprecated-icon.png',
            'deprecated' => [
                'since' => 'my extension v2',
                'until' => 'my extension v3',
                'replacement' => 'alternative-icon',
            ],
        ],
    ];

.. index:: Backend, ext:core
