.. include:: /Includes.rst.txt

.. _important-99366-1671021046:

==================================================================================
Important: #99366 - Add backward compatibility handling for frontend login signing
==================================================================================

See :issue:`99366`

Description
===========

The security fix for `https://typo3.org/security/advisory/typo3-core-sa-2022-013 <TYPO3-CORE-SA-2022-013>`_
enforced the `pid` HTTP parameter to be signed via HMAC during the frontend user authentication process.

It occurred that custom authentication services suffered from this strict requirements. To provide better
backward compatibility for those individual scenarios, the new `security.frontend.enforceLoginSigning`
feature flag has been introduced, which is enabled per default, but can be disabled individually.

.. code-block: php

    // disable signing the `pid` parameter for backward compatibility
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.frontend.enforceLoginSigning'] = false;

.. index:: Frontend, ext:felogin
