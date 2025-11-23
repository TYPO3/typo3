..  include:: /Includes.rst.txt

..  _breaking-68303-1743324411:

==================================================================
Breaking: #68303 - Make tt_content imagewidth/imageheight nullable
==================================================================

See :issue:`68303`

Description
===========

The default values of the fields `imagewidth` and `imageheight` in the
`tt_content` table are now set to `null`.

This change removes the awkward UI behavior where the fields were previously
set to `0` when no value was entered.

Impact
======

Custom queries might fail if they expect the fields to be `0` instead of `null`.

Affected installations
======================

TYPO3 installations that rely on the `imagewidth` and `imageheight` fields of the
`tt_content` table always being integers are affected.

Migration
=========

Use the "Media fields zero to null" upgrade wizard to update existing field values.

Also, modify your queries to handle `null` values instead of `0`.

..  index:: Backend, Frontend, NotScanned, ext:frontend
