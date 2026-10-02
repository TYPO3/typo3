..  include:: /Includes.rst.txt

..  _important-110175-1790179101:

=============================================================================
Important: #110175 - TypoScript _LOCAL_LANG.default is a last-resort fallback
=============================================================================

See :issue:`110175`

Description
===========

Label overrides in TypoScript via :typoscript:`_LOCAL_LANG` are now resolved
strictly by locale. Previously the key :typoscript:`default` was treated as
a synonym for English and overruled labels shipped in XLIFF files. It is now
only a last-resort fallback.

For the current locale, including its fallback locales, labels are resolved in
this order:

1.  :typoscript:`_LOCAL_LANG` override for the locale, for example
    :typoscript:`fr-LU`
2.  :typoscript:`_LOCAL_LANG` override for the language, for example
    :typoscript:`fr`
3.  Label from the XLIFF file of the locale or its fallback locales
4.  :typoscript:`_LOCAL_LANG.default`, only if the label exists nowhere else

The language key of a locale is written in lowercase, followed by a dash and
the uppercase country code, for example :typoscript:`fr-LU` or
:typoscript:`en-US`. Underscore spellings like :typoscript:`fr_LU` are
normalized, so both forms are accepted.

A locale configured with a country code and a code set, such as
:php:`fr_LU.utf8`, now falls back to its language (:php:`fr`) as expected,
instead of falling straight through to :typoscript:`default`.

Site language settings using :typoscript:`default` are converted to
:typoscript:`en` automatically.

Impact
======

Integrators who override English labels of an existing XLIFF file with
:typoscript:`_LOCAL_LANG.default` will no longer see the override applied,
as the XLIFF label now takes precedence.

Use the :typoscript:`en` or :typoscript:`en-US` key instead:

..  code-block:: typoscript

    # Before
    plugin.tx_myext._LOCAL_LANG.default.some_label = My label

    # After
    plugin.tx_myext._LOCAL_LANG.en.some_label = My label
    plugin.tx_myext._LOCAL_LANG.en-US.some_other_label = My US label

Overrides for other languages and locales are not affected. Labels that exist
in no XLIFF file still fall back to :typoscript:`_LOCAL_LANG.default`.

..  index:: Frontend, TypoScript, ext:core
