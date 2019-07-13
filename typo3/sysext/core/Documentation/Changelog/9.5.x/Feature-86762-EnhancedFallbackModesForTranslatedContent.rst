.. include:: ../../Includes.txt

================================================================
Feature: #86762 - Enhanced fallback modes for translated content
================================================================

See :issue:`86762`

Description
===========

Various content fallback options have been adapted to allow multiple scenarios when rendering
content in a different language than the default language (sys_language_uid=0).

The functionality of "fallbackChain" can now be defined in any kind of fallback type (see below).

The "fallbackChain" checks access / availability of a page translation of a language. If
this language does not exist, TYPO3 checks for other languages, and uses this language then
for showing content.

This results in three different kinds of rendering modes ("Fallback Type") for content in
translated pages, however it is necessary to understand the overlay concept when fetching
content in TYPO3 Frontend.

Using "language overlays" means that the default language records are fetched first.
Also, various "enable fields" (e.g. hidden / frontend user groups etc) are evaluated for the
default language. Each record then is "overlaid" with the record of the target language.

Not using "overlays" means that the default language is not considered at all.

No matter what type is chosen, records which do not have a localization parent ("l10n_parent")
will always be rendered in the target language.

The following "fallback types" exist:

1. "strict" -- Fetch the records in the default language, then overlay them with the target
language. If a record is not translated into the target language, then it is not shown at all.

This mode is typically used for 1:1 translations of fully different languages like
"English" (default) and "Danish" (translation).

2. "fallback" -- Fetch records from default language, and checks for a translation of
each record. If the record has no translation, the default language is shown.

This scenario is usually used when the default language is "German" but the translation
is "Swiss-German" where only different content elements are translated, but the rest is
a 1:1 translation.

3. "free" (new) -- Fetch all records from the target language directly without worrying about
the default language at all.

This is typically the case when a localized page may have fully different content than the
default language. E.g. "English" as default language, but only the most important content parts
are added in language "Swahili".


Impact
======

Existing installations with site configuration "fallback" will now render the non-translated
content (un-localized records), too.

Regardless of the fallback type, records without localization parent, and records set to "-1"
(All Languages) are always fetched.

.. index:: Frontend
