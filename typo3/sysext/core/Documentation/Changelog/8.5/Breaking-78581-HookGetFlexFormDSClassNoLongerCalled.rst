.. include:: /Includes.rst.txt

===========================================================
Breaking: #78581 - Hook getFlexFormDSClass no longer called
===========================================================

See :issue:`78581`

Description
===========

With the deprecation of :php:`BackendUtility::getFlexFormDS()` the hook :php:`getFlexFormDSClass` is
no longer called and there is no substitution available.


Impact
======

The hook is no longer called and flex form field manipulation by extensions does not happen anymore.


Affected Installations
======================

Extensions that extension flex form data structure definitions and use the hook :php:`getFlexFormDSClass`
for that purpose.


Migration
=========

Method :php:`BackendUtility::getFlexFormDS()` has been split into the methods
:php:`FlexFormTools->getDataStructureIdentifier()` and :php:`FlexFormTools->parseDataStructureByIdentifier()`.

Those two new methods now provide four hooks to allow manipulation of the flex form data structure
location and parsing. The methods and hooks are documented well, read their description for a deeper
insight on which combination is the correct one for a specific extension need.

The new hooks are very powerful and must be used with special care to be as future proof as possible.

Since the old hook is used by some widespread extensions, the core team prepared a transition for some
of them beforehand:

* EXT:news: The extension used the old hook to only remove a couple of fields from the flex
  form definition. This has been moved over to a "FormEngine" data provider: news_

* EXT:flux: Flux implements a completely own way of locating and pointing to the flex form
  data structure that is needed in a specific context. The default core resolving does not work
  here. Flux now implements the hooks :php:`getDataStructureIdentifierPreProcess` and
  :php:`parseDataStructureByIdentifierPreProcess` to specify an own "identifier" syntax
  and to resolve that syntax to a data structure later: flux_

* EXT:gridelements: Similar to flux, gridelements has a own logic to choose which specific
  data structure should be used. However, the data structures are located in database row fields,
  so the "record" syntax of the core can be re-used to refer to those. gridelements uses the hook
  :php:`getDataStructureIdentifierPreProcess` together with a small implementation in
  :php:`parseDataStructureByIdentifierPreProcess` for a fallback scenario: gridelements_

* EXT:powermail: Powermail allows extending and changing existing flex form data structure
  definition depending on page TS. To do that, it now implements hook
  :php:`getDataStructureIdentifierPostProcess` to add the needed pid to the existing identifier,
  and then implements hook :php:`parseDataStructureByIdentifierPostProcess` to manipulate the
  resolved data structure: powermail_

.. _news: https://github.com/georgringer/news/pull/155
.. _flux: https://github.com/FluidTYPO3/flux/pull/1203
.. _gridelements: https://review.typo3.org/#/c/50513/
.. _powermail: https://github.com/einpraegsam/powermail/pull/6

.. index:: PHP-API, FlexForm, Backend
