.. include:: ../../Includes.txt

==========================================================
Breaking: #79243 - Remove sys_language_softMergeIfNotBlank
==========================================================

See :issue:`79243`

Description
===========

The TypoScript setting :ts:`config.sys_language_softMergeIfNotBlank` has been removed
without any replacement. This is a result of removing the TCA setting
`mergeIfNotBlank` from the list of possible values for `l10n_mode`.


Migration
=========

Remove TypoScript setting :ts:`config.sys_language_softMergeIfNotBlank`.

.. index:: Frontend, TypoScript, TCA
