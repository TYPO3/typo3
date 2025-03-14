..  include:: /Includes.rst.txt

..  _important-103451-1793198547:

================================================================================
Important: #103451 - Language menu marks default language fallbacks as available
================================================================================

See :issue:`103451`

Description
===========

A language menu, built with :typoscript:`HMENU` and
:typoscript:`special = language` or with the
:php:`\TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor`, now marks a
language as available if the current page is not translated into it, but the
default language is part of its fallback chain (:yaml:`fallbacks` in the site
configuration).

Such a page is rendered in the default language when it is called in that
language. Before, the language menu still marked the language as not available
(:typoscript:`USERDEF1` and :typoscript:`USERDEF2` item states,
:html:`available` set to `0` by the :php:`LanguageMenuProcessor`), unless
:typoscript:`special.normalWhenNoLanguage` was set. Languages that fell back to
another, translated language were already marked as available, so all fallbacks
are now treated alike.

A language whose fallback chain does not contain the default language is still
marked as not available if neither it nor one of its fallbacks has a page
translation, as such a page is not rendered. The page option "Hide page if no
translation for current language exists" (:sql:`l18n_cfg`) keeps marking an
untranslated language as not available.

Language menus that relied on the previous state to hide or disable such
languages need to check for an existing page translation instead.

..  index:: Frontend, TypoScript, ext:frontend
