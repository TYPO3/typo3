.. include:: ../../Includes.txt

================================================================
Important: #95384 - TCA internal_type=db optional for type=group
================================================================

See :issue:`95384`

Description
===========

The TCA option `internal_type` of TCA type `group` defines which type
of record can be referenced. Valid values are `folder` and `db`.

Since `db` is the most common use case, TYPO3 now uses this as default.
Extension authors can therefore remove the `internal_type=db` option
from TCA type `group` fields.

.. index:: Backend, TCA, ext:backend
