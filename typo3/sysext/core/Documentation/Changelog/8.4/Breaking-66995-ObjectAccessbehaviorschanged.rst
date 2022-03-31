.. include:: /Includes.rst.txt

=================================================
Breaking: #66995 - ObjectAccess behaviors changed
=================================================

See :issue:`66995`

Description
===========

The following changes have been implemented in ObjectAccess:

* Uses more native PHP methods where reasonable
* Uses fewer method calls where reasonable
* Gets rid of a variables passed by reference
* More cases return ``null`` rather than throw Exceptions
* Fastest decisions and access methods come first
* Reflection-based access isolated to edge cases and
  access with the "force direct access" flag enabled.
* Sacrifices ability to read objects of types other
  than persisted objects contained in an ObjectStorage
  or subclass of ObjectStorage.
* Changes verdict from ``false`` to ``true`` when
  determining if a dynamically added property exists on
  an object (these are by definition publicly accessible).


Impact
======

* Performance improvement; optimising for most frequent case coming first, skipping expensive Reflection etc.
* Reflection based access only happens when "force" flag is set to true in getProperty / getPropertyPath
* Removes support for using objects of types other than persisted objects in any ObjectStorage implementation.
* Changes behavior when accessing dynamically added properties on objects from previously false to now true (by definition they are public). This
  improves compatibility with JSON sources decoded to stdClass for example.


Affected Installations
======================

* Any code using `ObjectStorage` combined with objects that are not persistent objects (Extbase models)
* Any code passing `stdClass` with dynamic properties to ``ObjectAccess::getProperty()`` and expecting ``null`` (real value will now be returned)


Migration
=========

* If you have any `ObjectStorage` implementation containing other types than persisted objects, switch to any alternative (`Iterator`, `ArrayAccess`, etc.)
* If you have Extbase code or Fluid templates using `ObjectAccess` to read `stdClass` instances with dynamically added properties and your code expects a
  false verdict for such properties (highly unlikely!) inverse any conditions depending on the resolved value being null or empty to expect the actual
  value of the value you address.

.. index:: PHP-API, ext:extbase
