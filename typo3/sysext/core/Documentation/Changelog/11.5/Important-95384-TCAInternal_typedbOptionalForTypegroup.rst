.. include:: /Includes.rst.txt

================================================================
Important: #95384 - TCA internal_type=db optional for type=group
================================================================

See :issue:`95384`

Description
===========

The TCA option :php:`internal_type` of TCA type :php:`group` defines which type
of record can be referenced. Valid values are :php:`folder` and :php:`db`.

Since :php:`db` is the most common use case, TYPO3 now uses this as default.
Extension authors can therefore remove the :php:`internal_type=db` option
from TCA type :php:`group` fields.

.. index:: Backend, TCA, ext:backend
