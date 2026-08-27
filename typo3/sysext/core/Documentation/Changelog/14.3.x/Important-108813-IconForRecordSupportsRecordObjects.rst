..  include:: /Includes.rst.txt

..  _important-108813-1787861512:

=======================================================
Important: #108813 - Record icons accept record objects
=======================================================

See :issue:`108813`

Description
===========

Rendering the icon of a record required a plain database row and its table name.
Code already working with objects implementing
:php:`\TYPO3\CMS\Core\Domain\RecordInterface` had to rebuild an array just to
render an icon, although such a record already knows its own table.

:php:`\TYPO3\CMS\Core\Imaging\IconFactory` therefore provides a new method
:php:`getIconForRecordObject()`, which takes the record object instead of a
table name and a row:

..  code-block:: php

    // Plain database row
    $icon = $iconFactory->getIconForRecord('tt_content', $row, IconSize::SMALL);

    // Record object
    $icon = $iconFactory->getIconForRecordObject($record, IconSize::SMALL);

The Fluid ViewHelper :html:`<core:iconForRecord>` gained a new argument
:html:`record`, which is interchangeable with :html:`row`. Both accept a plain
database row as well as a record object, and exactly one of them must be given.
The argument :html:`table` is no longer mandatory: it is required for row
arrays only and is not evaluated for record objects.

..  code-block:: html
    :caption: Before

    <core:iconForRecord table="tt_content" row="{CType: item.CType}" />

..  code-block:: html
    :caption: After

    <core:iconForRecord record="{record}" />

The record's raw database row is used to determine both the type icon and the
status overlays, so overlays for disabled or scheduled records are rendered
the same way as for a plain row array.

Impact
======

Existing usages with a table name and a plain array continue to work
unchanged. Extension authors can now pass record objects directly, which is
the recommended way when the surrounding code already works with records.

..  index:: Backend, Fluid, PHP-API, ext:core
