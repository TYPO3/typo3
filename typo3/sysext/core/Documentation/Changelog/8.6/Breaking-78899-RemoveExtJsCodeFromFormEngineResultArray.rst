.. include:: /Includes.rst.txt

==================================================================
Breaking: #78899 - Remove `extJSCODE` from FormEngine result array
==================================================================

See :issue:`78899`

Description
===========

The key :php:`extJSCODE` in the array returned by FormEngine's :php:`Container` and :php:`Element` (initialized
in :php:`AbstractNode->initializeResultArray()`) has been removed.


Impact
======

JavaScript code added to :php:`extJSCODE` by custom elements will not be evaluated anymore.


Affected Installations
======================

Search extensions for the string :php:`extJSCODE`. This array is used rather seldom, but if there are matches
in combination with Backend Form classes, they should be adapted.


Migration
=========

For a simple solution, add according JavaScript to the return key :php:`additionalJavaScriptPost` for now.
Both keys were used nearly identically anyway. Be aware that both keys :php:`additionalJavaScriptPost` and
:php:`additionalJavaScriptSubmit` are target of a later removal as soon as a better JavaScript side event handling
for those scenarios is in place. See if the current code injected at this point could be done with
casual :js:`RequireJsModules` instead already.

.. index:: Backend, JavaScript, PHP-API
