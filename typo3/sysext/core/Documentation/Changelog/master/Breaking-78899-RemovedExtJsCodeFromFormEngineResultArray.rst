.. include:: ../../Includes.txt

==================================================================
Breaking: #78899 - Removed ExtJs code from FormEngine result array
==================================================================

See :issue:`78899`

Description
===========

The array key `extJSCODE` of FormEngine `Container` and `Element` return array that is initialized
in `AbstractNode->initializeResultArray()` has been removed.


Impact
======

Custom elements adding JavaScript to this array key will not be evaluated anymore.


Affected Installations
======================

Search extensions for the string `extJSCODE`. This array is used rather seldom, but if there are matches
in combination with Backend Form classes, they should be adapted.


Migration
=========

For a simple solution, add according JavaScript to the return key `additionalJavaScriptPost` for now.
Both keys were used nearly identically anyway. Be aware that both keys `additionalJavaScriptPost` and
`additionalJavaScriptSubmit` are target of a later removal as soon as a better JavaScript side event handling
for those scenarios is in place. See if the current code injected at this point could be done with
casual `RequireJsModules` instead already.

.. index:: Backend, JavaScript, PHP-API