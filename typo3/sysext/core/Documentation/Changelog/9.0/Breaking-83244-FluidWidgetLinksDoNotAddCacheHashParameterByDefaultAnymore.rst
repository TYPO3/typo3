.. include:: /Includes.rst.txt

=======================================================================================
Breaking: #83244 - Fluid Widget Links do not add cacheHash parameter by default anymore
=======================================================================================

See :issue:`83244`

Description
===========

When using links for fluid widgets (like Paginator widgets) it is not possible to disable the cHash calculation.

A new argument `useCacheHash` for the :html:`<f:widget.link>` and the :html:`<f:widget.uri>` ViewHelpers has been added
to re-enable the previous behaviour.

Additionally, using cHash and addQueryString is counterproductive for deterministic caching purposes,
thus this combination should not be set by TYPO3 core by default.


Impact
======

Using the :html:`<f:widget.link>` or :html:`<f:widget.uri>` ViewHelper will not generate a cHash anymore.


Affected Installations
======================

Installations using extensions that are built around Fluid widgets.


Migration
=========

None. If necessary, activate the cHash calculation by using the newly introduced Fluid argument "useCacheHash".

.. index:: Fluid, NotScanned
