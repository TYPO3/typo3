.. include:: /Includes.rst.txt

===========================================================================
Important: #80266 - Moved config.sys_language_softExclude to compatibility7
===========================================================================

See :issue:`80266`

Description
===========

The TypoScript option :typoscript:`config.sys_language_softExclude` to set
certain TCA table fields to ``l10n_mode=exclude`` during frontend request
runtime has been moved to compatibility7.

If any installation depends on this option in the TYPO3 frontend, the
extension should be installed.

However, as the TCA option ``l10n_mode=exclude`` has been superseded
by the TCA option ``allowLanguageSynchronization`` the actual use-case
for this TypoScript setting should be re-evaluated.

.. index:: TypoScript, Frontend, TCA
