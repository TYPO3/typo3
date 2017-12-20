
.. include:: ../../Includes.txt

===============================================================
Deprecation: #65938 - Discourage usage of "$TSFE->anchorPrefix"
===============================================================

See :issue:`65938`

Description
===========

The property "anchorPrefix" within TypoScriptFrontendController is set to the relative path from the public site
root when `config.baseURL` is set, and can be used to prefix local anchors with that prefix. The option has been
marked as deprecated in favor of using `config.absRefPrefix` when this functionality is needed.


Affected installations
======================

All installations or extensions relying on the TypoScriptFrontendController property "anchorPrefix" running
with the TypoScript option `config.baseURL` enabled.


Migration
=========

Use the PHP code below directly to fetch the information when needing baseURL and the anchorPrefix option.

.. code-block:: php

	GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), strlen(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'))

Alternatively, use `config.absRefPrefix` to achieve the same result.


.. index:: TypoScript, Frontend
