.. include:: ../../Includes.txt

=======================================================================
Important: #82489 - Use dedicated property to hide newRecordLink button
=======================================================================

See :issue:`82489`

Description
===========

Previously the TCA property :php:`['appearance']['enabledControls']['new']`
which is intended to manage the display of the inline records controls `new`
button was misused to also hide the `newRecordLink` button of the whole inline
column.

The property is therefore from now on only used for the controls section of each
inline record.

To hide the `newRecordLink` button, independent of the controls `new` button,
use the already existing property :php:`['appearance']['levelLinksPosition']`
with `none` as value.

.. index:: Backend, TCA, ext:backend
