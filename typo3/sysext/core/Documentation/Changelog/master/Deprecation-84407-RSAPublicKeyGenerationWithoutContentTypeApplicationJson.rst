.. include:: ../../Includes.txt

========================================================================================
Deprecation: #84407 - RSA public key generation without "Content-Type: application/json"
========================================================================================

See :issue:`84407`

Description
===========

The default response of the :php:`RsaPublicKeyGenerationController` eID script was broken since it
claimed to return a JSON response but in fact returned a simple string containing a concatenation of
public key modulus and exponent.

The eID script now returns a proper JSON response if requested with the
`Content-Type: application/json` HTTP header:

.. code-block:: javascript

    {
        "publicKeyModulus": "ABC...",
        "exponent": "10..."
    }


Impact
======

Extensions performing custom AJAX requests against the :php:`RsaPublicKeyGenerationController`
eID script without the `Content-Type: application/json` HTTP header will trigger a deprecation
warning in v9 and an error response in v10.


Affected Installations
======================

Sites which do not use the default RSA encryption JavaScript to handle form value encryption.


Migration
=========

The default RSA encryption JavaScript has been migrated, custom implementations must add the
`Content-Type: application/json` HTTP header to AJAX requests and parse the JSON response
accordingly.

.. index:: Backend, Frontend, JavaScript, PHP-API, FullyScanned, ext:rsaauth
