
.. include:: ../../Includes.txt

========================================================
Breaking: #66754 - Remove RenderingContextAwareInterface
========================================================

See :issue:`66754`

Description
===========

The `RenderingContextAwareInterface` allowed objects to get the `RenderingContext` set while being
accessed from inside a Fluid template. This makes optimization of variable access in Fluid difficult
and seems to be an unused feature. Therefore it has been removed.

Impact
======

For implementations of `RenderingContextAwareInterface` the change breaks without any simple replacement.
Functionality would have to be replicated in userland code. But as there are no known implementations
the expected impact is rather low.


Breaking interface changes
--------------------------

* The `RenderingContextAwareInterface` has been removed. There is no replacement.


.. index:: Fluid, PHP-API
