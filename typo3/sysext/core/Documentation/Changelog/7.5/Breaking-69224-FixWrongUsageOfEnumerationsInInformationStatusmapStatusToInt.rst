
.. include:: ../../Includes.txt

=========================================================================================
Breaking: #69224 - Fix wrong usage of enumerations in InformationStatus::mapStatusToInt()
=========================================================================================

See :issue:`69224`

Description
===========

The `InformationStatus` enumeration provides a `mapStatusToInt()` method.

* The method expects a string but should expect an enum of itself.
* The method logic is not what is expected from an enumeration method as it does not do any logic comparison.

Therefore it has been replaced by `isGreaterThan()` as this was the logic that has been checked
everywhere `mapStatusToInt()` has been used.


Impact
======

The method `InformationStatus::mapStatusToInt()` has been replaced by `InformationStatus::isGreaterThan()` and all
usages have been replaced by the new method / logic.
As the `InformationStatus` Enum has been introduced in 7.4 it should not be used by any public API and
therefore the change should not have much impact.


.. index:: PHP-API
