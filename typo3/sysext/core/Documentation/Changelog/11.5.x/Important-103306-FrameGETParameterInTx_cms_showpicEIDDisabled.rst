.. include:: /Includes.rst.txt

.. _important-103306-1714976257:

=======================================================================
Important: #103306 - Frame GET parameter in tx_cms_showpic eID disabled
=======================================================================

See :issue:`103306`

Description
===========

The show image controller (eID `tx_cms_showpic`) lacks a cryptographic
HMAC-signature on the frame HTTP query parameter (e.g.
`/index.php?eID=tx_cms_showpic?file=3&...&frame=12345`).
This allows adversaries to instruct the system to produce an arbitrary number of
thumbnail images on the server side.

To prevent uncontrolled resource consumption, the frame HTTP query parameter is
now ignored, since it could not be used by core APIs.

The new feature flag
`security.frontend.allowInsecureFrameOptionInShowImageController` — which is
disabled per default — can be used to reactivate the previous behavior:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.frontend.allowInsecureFrameOptionInShowImageController'] = true;


.. index:: Frontend, NotScanned, ext:frontend
