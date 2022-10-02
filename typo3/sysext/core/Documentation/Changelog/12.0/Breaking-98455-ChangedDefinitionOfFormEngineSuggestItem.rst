.. include:: /Includes.rst.txt

.. _breaking-98455-1664350742:

================================================================
Breaking: #98455 - Changed definition of FormEngine suggest item
================================================================

See :issue:`98455`

Description
===========

The definition of an item used in the FormEngine auto-suggest list has changed.
The following properties are unused now and were therefore removed:

* `text` - contained a pre-composed text including markup rendered in an result
  item
* `style` - was unused already
* `class` - was unused already
* `sprite` - contained a pre-composed markup of the icon being rendered

The property `icon` is added and is an array containing the `identifier` of an
icon and optionally an `overlay` identifier.

Example:

..  code-block:: php

    $icon = $this->iconFactory->getIconForRecord($this->table, $row, Icon::SIZE_SMALL);
    $entry = [
        // ...
        'icon' => [
            'identifier' => $icon->getIdentifier(),
            'overlay' => $icon->getOverlayIcon()?->getIdentifier(),
        ],
    ];

Impact
======

The removed properties `text`, `style`, `class`, and `sprite` are ignored if
still supplied by custom suggest wizards. Also, the `icon` property must be provided.

The removed property `text` also affects potential :php:`renderFunc` callbacks
configured in the field's TCA.

Affected installations
======================

All extensions having a custom suggest wizard or providing a  :php:`renderFunc`
are affected.

Migration
=========

Albeit rarely used, custom suggest wizards need to adjust the suggest items in
their :php:`queryTable()` method. Potential render methods defined in the TCA
field's :php:`renderFunc` config may not manipulate an entry's `text` property.

.. index:: Backend, PHP-API, NotScanned, ext:backend
