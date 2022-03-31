.. include:: /Includes.rst.txt

===========================================================
Feature: #57594 - Optimize ReflectionService Cache handling
===========================================================

See :issue:`57594`

Description
===========

Since its beginnings, Extbase came along with two main caches for reflection data,
`extbase_reflection` and `extbase_object`. The latter mostly stored information that were relevant
to the dependency injection, like inject methods, inject properties and constructor parameters. The
information was gathered by actual reflection and by analysing doc blocks of properties and methods.

`extbase_reflection` stored similar reflection and doc block data about objects but mainly for the
parts outside dependency injection.

For example, the validation resolver used it to identify :php:`@validate` tags, the ActionController used
it to identity which properties not to validate. The ORM also used it a lot to find annotated types
via :php:`@var`.

There were a few issues with these two approaches:

* A lot of redundant data was fetched

* Data was fetched multiple times at different locations

* The `extbase_reflection` cache was stored each plugin separately, resulting in a lot of redundant
  cache data for each plugin cache

* At a lot of places, the reflection service was used to reflect objects, but the data wasn't cached
  or taken from a cache resulting in performance drawbacks


Impact
======

* The `extbase_object` cache has been removed completely and all necessary information about objects,
  mainly :php:`@inject` functionality, is now fetched from the `ReflectionService` as well.

* The `ReflectionService` does still create `ClassSchema` instances but these were improved a lot.
  All necessary information is now gathered during the instantiation of ClassSchema instances. This
  means that all necessary data is fetched once and then it can be used everywhere making any further
  reflection superfluous.

* As runtime reflection has been removed completely, along with it several reflection classes, that
  analyzed doc blocks, have been removed as well. These are no longer necessary.

* The `extbase_reflection` cache is no longer plugin based and will no longer be stored in the
  database in the first place. Serialized `ClassSchema` instances will be stored in `typo3temp/var/cache` or
  `var/cache/` for composer-based installations.

.. index:: PHP-API, ext:extbase
