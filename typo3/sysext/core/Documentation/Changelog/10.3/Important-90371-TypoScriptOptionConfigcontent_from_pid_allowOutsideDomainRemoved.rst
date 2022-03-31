.. include:: /Includes.rst.txt

========================================================================================
Important: #90371 - TypoScript option config.content_from_pid_allowOutsideDomain removed
========================================================================================

See :issue:`90371`

Description
===========

TYPO3's Site Handling - introduced in TYPO3 v9 - allows defining
multiple sites within one installation, whereas before all configuration was based on domain records.
The TypoScript option :typoscript:`config.content_from_pid_allowOutsideDomain` was used to limit
the page property option "Show content from this page instead" (:typoscript:`pages.content_from_pid`) to be
evaluated outside of the current page tree which was ineffective since the usage of Site Handling.

The option serves no purpose anymore and has been removed.

.. index:: Frontend, TypoScript, ext:frontend
