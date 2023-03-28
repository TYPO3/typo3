.. include:: /Includes.rst.txt

.. _feature-100187-1679001588:

=====================================================
Feature: #100187 - ICU-based date and time formatting
=====================================================

See :issue:`100187`

Description
===========

TYPO3 now supports rendering date and time based on formats/patterns defined by
the International Components for Unicode standard (ICU).

TYPO3 previously only supported rendering of dates based on the PHP-native
functions :php:`date()` and :php:`strftime()`.

However, :php:`date()` can only format dates with English texts, such as
"December" as non-localized values, the C-based :php:`strftime()` function works
only with the locale defined in PHP and availability in the underlying operating
system.

In addition, ICU-based date and time formatting is much more flexible in
rendering, as it ships with default patterns for date and time (namely
`FULL`, `LONG`, `MEDIUM` and `SHORT`) which are based on the given locale.

This means, that when the locale `en-US` is given, the short date is rendered
as `mm/dd/yyyy` whereas `de-AT` uses the `dd.mm.yyyy` syntax automatically,
without having to define a custom pattern just by using the SHORT default
pattern.

In addition, the patterns can be adjusted more fine-grained, and can easily
deal with time zones for output when DateTime objects are handed in.

TYPO3 also adds prepared custom patterns:

* `FULLDATE` (like `FULL`, but only the date information)
* `FULLTIME` (like `FULL`, but only the time information)
* `LONGDATE` (like `LONG`, but only the date information)
* `LONGTIME` (like `LONG`, but only the time information)
* `MEDIUMDATE` (like `MEDIUM`, but only the date information)
* `MEDIUMTIME` (like `MEDIUM`, but only the time information)
* `SHORTDATE` (like `SHORT`, but only the date information)
* `SHORTTIME` (like `SHORT`, but only the time information)

See https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
for more information on the patterns.


Impact
======

A new stdWrap feature called `formattedDate` is added, and the new formatting
can also be used in Fluid's :html:`<f:format.date>` ViewHelper.

The locale is typically fetched from the locale of the site language (stdWrap or
ViewHelper), or the backend user's language (in backend context) for the
ViewHelper usages.

Examples for stdWrap:

..  code-block:: typoscript

    page.10 = TEXT
    page.10.value = 1998-02-20 3:00:00
    # see all available options https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
    page.10.formattedDate = FULL
    # optional, if a different locale is wanted other than the Site Language's locale
    page.10.formattedDate.locale = de-DE

will result in "Freitag, 20. Februar 1998 um 03:00:00 Koordinierte Weltzeit".

..  code-block:: typoscript

    page.10 = TEXT
    page.10.value = -5 days
    page.10.formattedDate = FULL
    page.10.formattedDate.locale = fr-FR

will result in "jeudi 9 mars 2023 à 21:40:49 temps universel coordonné".

Examples for Fluid `<f:format.date>` ViewHelper:

..  code-block:: html

    <f:format.date pattern="dd. MMMM yyyy" locale="de-DE">{date}</f:format.date>

will result in "20. Februar 1998".

As soon as the :html:`pattern` attribute is used, the :html:`format` attribute
is disregarded.

Both new ViewHelper arguments are optional.

.. index:: Fluid, PHP-API, TypoScript, ext:core
