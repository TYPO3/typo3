.. include:: /Includes.rst.txt

=====================================
Feature: #84729 - New TCA type "slug"
=====================================

See :issue:`84729`

Description
===========

A new TCA field type called `slug` has been added to TYPO3 Core. Its main purpose is to define parts of a URL
path to generate and resolve URLs.

With a URL like `https://www.typo3.org/ch/community/values/core-values/` a URL slug is typically a part like
`/community` or `/community/values/core-values`.

Within TYPO3, a slug is always part of the URL "path" - it does not contain scheme, host, HTTP verb, etc.

A slug is usually added to a TCA-based database table, containing some rules for evaluation and definition.

In contrast to concepts within RealURL of "URL segments", a slug is a segment of a URL, but it is not limited
to be separated by slashes. Therefore, a slug can contain slashes.

In the future, it could be possible to generate slugs for any TCA table, but its's main usage will be for the "pages"
TCA structure.

If a TCA table contains a field called "slug", it needs to be filled for every existing record. It can
be shown and edited via regular Backend Forms, and is also evaluated during persistence via DataHandler.

The default behaviour of a slug is as follows:

* A slug only contains characters which are allowed within URLs. Spaces, commas and other special characters are converted to a fallback character.
* A slug is always lower-cased.
* A slug is unicode-aware.

The following options apply to the new TCA type::

	'config' => [
		'type' => 'slug',
		'generatorOptions' => [
			'fields' => ['title', 'nav_title'],
			'fieldSeparator' => '/',
			'prefixParentPageSlug' => true
		],
		'fallbackCharacter' => '-',
		'eval' => 'uniqueInSite'
	]

The new `eval` option `uniqueInSite` has been introduced to evaluate if a record is unique in a page tree (specific to a
language).

The new slug TCA type allows for two `eval` options `uniqueInSite` or `uniqueInPid` (useful for third-party
records), and no other eval setting is checked for. It is possible to set both eval options, however it is
recommended not to do so.

It is possible to build a default value from the rootline (very helpful for pages, or categorized slugs),
but also to just generate a "speaking" segment from e.g. a news title.

Sanitation and Validation configuration options apply when persisting a record via DataHandler.

In the backend forms a validation happens by an AJAX call, which immediately checks any input and receives
a new proposal in case the slug is already used.

.. index:: TCA, ext:core
