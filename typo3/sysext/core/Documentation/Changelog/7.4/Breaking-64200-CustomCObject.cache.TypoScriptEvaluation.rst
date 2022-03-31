
.. include:: /Includes.rst.txt

=================================================================
Breaking: #64200 - Custom [cObject].cache.* TypoScript evaluation
=================================================================

See :issue:`64200`

Description
===========

The `stdWrap.cache.` property is now available as first-class function to all
content objects. This skips the rendering even for content objects that evaluate
`stdWrap` after rendering (e.g. `COA`).

Additionally, stdWrap support is added to key, lifetime and tags.


Impact
======

If you've previously used the `cache.` property in your custom cObject,
this will now fail, because `cache.` is unset to avoid double caching.

`stdWrap.cache` continues to exist and can be used as before. However
the top level `stdWrap` of certain cObjects (e.g. `TEXT` cObject)
will not evaluate `cache.` as part of `stdWrap`, but before starting
the rendering of the cObject. In conjunction the storing will happen
after the `stdWrap` processing right before the content is returned.

Top level `cache.` will not evaluate the hook
`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap_cacheStore']`
any more.


Affected Installations
======================

All installations with custom `cObject` implementations which make use of the `cache.` property.

Installations that purposely rely on the content object being evaluated before the cache is tried.

Installations that rely on the order of the `cache.` evaluation.

Installations that make use of the hook
`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap_cacheStore']`
on top level `cache.`.


Migration
=========

Rename your property or rely on the Core implementation.

If you need `cache.` being evaluated as part of `stdWrap`, please move it down one level
by writing `stdWrap.cache` instead.

If you used the hook
`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap_cacheStore']`,
please use `stdWrap` and the available hooks inside `stdWrap` to achieve your goal.


.. index:: PHP-API, TypoScript, Frontend
