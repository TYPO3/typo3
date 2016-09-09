
.. include:: ../../Includes.txt

====================================================================================
Deprecation: #65934 - "Prefix Local Anchors" functionality moved to legacy extension
====================================================================================

See :issue:`65934`

Description
===========

Prefixing local anchors is not considered best practice in web sites anymore as the same is achieved with
absolute prefixes for links (see `config.absRefPrefix`). Therefore the according functionality has been moved to
the legacy extension EXT:compatibility6.

The following TypoScript option has been marked for deprecation:

.. code-block:: ts

	config.prefixLocalAnchors

The following PHP methods have been marked for deprecation:

.. code-block:: php

	TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::prefixLocalAnchorsWithScript()
	TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::doLocalAnchorFix()


Impact
======

The content output of the TYPO3 frontend is not prefixed with local anchors anymore unless EXT:compatibility6 is loaded.


Affected installations
======================

Any installation having the TypoScript option `config.prefixLocalAnchors` set will have different behaviour in the
frontend rendering.


Migration
=========

For TYPO3 CMS 7, installing EXT:compatibility6 brings back the existing functionality.
