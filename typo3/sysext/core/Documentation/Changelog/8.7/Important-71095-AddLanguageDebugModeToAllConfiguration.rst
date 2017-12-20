.. include:: ../../Includes.txt

================================================================
Important: #71095 - Add language debug mode to All Configuration
================================================================

See :issue:`71095`

Description
===========

Previously it was possible to set :php:`$TYPO3_CONF_VARS['BE']['lang']['debug']`
in order to enable debug in LanguageService.

However this could not be configured in the install tool.
In order to enable this possibility it has been renamed to
:php:`$TYPO3_CONF_VARS['BE']['languageDebug']`

.. index:: Backend, LocalConfiguration
