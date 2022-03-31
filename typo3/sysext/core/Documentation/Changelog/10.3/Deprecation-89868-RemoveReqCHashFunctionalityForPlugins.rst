.. include:: /Includes.rst.txt

===============================================================
Deprecation: #89868 - Remove reqCHash functionality for plugins
===============================================================

See :issue:`89868`

Description
===========

Extbase and pi-based plugins that are non-cacheable could previously
require the validation of the cHash GET parameter
in order to validate GET parameters against the "cHash".

In Extbase plugins, this could be configured via a TypoScript feature toggle (enabled by default):

:typoscript:`config.tx_extbase.features.requireCHashArgumentForActionArguments = 1`

In Pi-based plugins the public property :php:`AbstractPlugin->pi_checkCHash`
was used to enable the cHash validation for non-cacheable plugins.

Both plugin systems triggered the method :php:`TypoScriptFrontendController->reqCHash` which
validated relevant GET parameters. However, the :php:`PageArgumentValidator` PSR-15 middleware now
always validates the cHash, so a plugin does not need to know about cHash validation anymore and
therefore does not need to set the option.

This means the options are not needed anymore, as the validation already happens during the Frontend
request handling process. The options are removed.

In addition, the method :php:`TypoScriptFrontendController->reqCHash()` has been marked as deprecated and
is not in use anymore.


Impact
======

Setting the option in Extbase or Pi-Base has no effect anymore.

Calling the PHP method :php:`TypoScriptFrontendController->reqCHash()`
will trigger a PHP :php:`E_USER_DEPRECATED` error.

Internal classes such as the :php:`CacheHashEnforcer` are removed.


Affected Installations
======================

TYPO3 installations with plugins, where one of the options is set,
or where the PHP method is called directly.


Migration
=========

Remove the options / flags as they have no effect in TYPO3 v10 anymore.

Calling the method directly is also not needed, as the PageArgumentValidator is executing this
validation now at every request.

.. index:: Frontend, PartiallyScanned, ext:frontend
