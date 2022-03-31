
.. include:: /Includes.rst.txt

==================================================
Feature: #64200 - Allow individual content caching
==================================================

See :issue:`64200`

Description
===========

The `stdWrap.cache.` property is now available as first-class function to all
content objects. This skips the rendering even for content objects that evaluate
`stdWrap` after rendering (e.g. `COA`).

Usage:

.. code-block:: typoscript

	page = PAGE
	page.10 = COA
	page.10 {
		cache.key = coaout
		cache.lifetime = 60
		#stdWrap.cache.key = coastdWrap
		#stdWrap.cache.lifetime = 60
		10 = TEXT
		10 {
			cache.key = mycurrenttimestamp
			cache.lifetime = 60
			data = date : U
			strftime = %H:%M:%S
			noTrimWrap = |10: | |
		}
		20 = TEXT
		20 {
			data = date : U
			strftime = %H:%M:%S
			noTrimWrap = |20: | |
		}
	}

The commented part is `stdWrap.cache.` property available since 4.7,
that does not stop the rendering of `COA` including all sub-cObjects.

Additionally, stdWrap support is added to key, lifetime and tags.


Impact
======

If you've previously used the `cache.` property in your custom cObject,
this will now fail, because `cache.` is unset to avoid double caching.
You are encouraged to rely on the core methods for caching cObjects or
rename your property.

`stdWrap.cache` continues to exists and can be used as before. However
the top level `stdWrap` of certain cObjects (e.g. `TEXT` cObject)
will not evaluate `cache.` as part of `stdWrap`, but before starting
the rendering of the cObject. In conjunction the storing will happen
after the `stdWrap` processing right before the content is returned.

Top level `cache.` will not evaluate the hook
`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap_cacheStore']`
any more.


.. index:: PHP-API, TypoScript, Frontend
