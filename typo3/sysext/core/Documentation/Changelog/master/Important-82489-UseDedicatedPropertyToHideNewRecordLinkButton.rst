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

To still enable extension authors to hide the `newRecordLink` button, independent
of the controls `new` button, a new property :php:`['appearance']['showNewRecordLink']`
is now available for TCA type `inline`.

For backwards compatibility the `newRecordLink` button is only hidden if
`showNewRecordLink` is explicit set to :php:`FALSE`. If not set the button is shown.

.. index:: Backend, TCA, ext:backend
