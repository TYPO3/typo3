
.. include:: /Includes.rst.txt

==================================================================
Breaking: #75357 - $TYPO3_CONF_VARS[BE][lockSSL] option is boolean
==================================================================

See :issue:`75357`

Description
===========

The setting :php:`$GLOBALS[TYPO3_CONF_VARS][BE][lockSSL]` which forces requests to the TYPO3 Backend to be transferred
via SSL, has been changed to only allow boolean values.

The settings previously allowed three options:

* `lockSSL` set to `0` - Don't force a SSL connection at all
* `lockSSL` set to `1` - If the incoming request to the TYPO3 backend is a non-SSL request, an exception was thrown
* `lockSSL` set to `2` - If the incoming request is a non-SSL-request, redirect to the SSL-enabled URL

The option `1` has been removed without substitution, allowing the following variants:

* `lockSSL` set to `false` - Don't force a SSL connection at all
* `lockSSL` set to `true` - If the incoming request is a non-SSL-request, redirect to the SSL-enabled URL


Impact
======

If the option was set previously to `1`, the exception is not thrown but a redirect will now happen.
The same behavior as the existing option `2`.


Affected Installations
======================

TYPO3 instances having the option above set to `1`.

.. index:: LocalConfiguration, Backend
