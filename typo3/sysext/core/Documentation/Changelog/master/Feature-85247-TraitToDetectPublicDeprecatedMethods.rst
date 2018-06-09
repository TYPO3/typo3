.. include:: ../../Includes.txt

===========================================================
Feature: #85247 - Trait to detect public deprecated methods
===========================================================

See :issue:`85247`

Description
===========

The trait :php:`TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait` has been added
to allow setting public methods to protected in a backwards compatible way.

The core uses this trait to set public methods that to should be protected or private but
are accessible codewise for historical reasons, while extensions using the methods do not
break, but a deprecation note is logged.

Classes using this trait have a property :php:`$deprecatedPublicMethods` that lists all
methods covered by the trait.


Impact
======

Core classes using this trait log deprecation notes if an extension uses a method that
has been made protected using the trait functionality.

.. index:: PHP-API