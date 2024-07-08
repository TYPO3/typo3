..  include:: /Includes.rst.txt

..  _breaking-68303-1743324411:

==================================================================
Breaking: #68303 - Make tt_content imagewidth/imageheight nullable
==================================================================

See :issue:`68303`

Description
===========

The default value of the fields `imagewidth` and `imageheight` of the
`tt_content` table is now `null`. This removes the awkward UI behavior of the
fields being set to `0` if no value is entered.


Impact
======

Custom queries might fail if they expect the fields to be `0` instead of `null`.


Affected installations
======================

TYPO3 installation relying on fields `imagewidth` and `imageheight` of the
`tt_content` table being always an integer.


Migration
=========

Use the provided upgrade wizard to update the default value of the fields.

Also modify your queries to handle `null` values instead of `0`.

..  index:: Backend, Frontend, NotScanned, ext:frontend
