=======================================================================
Breaking: #66758 - Refactor property access in compiled fluid templates
=======================================================================

Description
===========

Refactors property access in Fluid templates to use a closure that calculates the getters and/or array
access keys to resolve the property path and self replaces this after the first rendering in the compiled
template. This improves property access dramatically after the first request.

Impact
======

This breaks if you access different types of data with the same path. For example if you have an array in
which arrays and objects are nested on the same level.

Example::

  Array (available in Fluid as {data})
    Object (getFoo())
    Array['foo']

  <f:for each="data" as="element">
  	{element.foo}
  </f:for>

This code would break with the change as the closure would determine that accessing {element.foo} needs to
use the getter method `getFoo()` so accessing the second element which is an array would break as fluid would
try to access `foo` as well with `getFoo()`.