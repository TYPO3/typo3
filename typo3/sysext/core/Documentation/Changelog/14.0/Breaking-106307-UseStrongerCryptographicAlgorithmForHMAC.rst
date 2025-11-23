..  include:: /Includes.rst.txt

..  _breaking-106307-1763824774:

=================================================================
Breaking: #106307 - Use stronger cryptographic algorithm for HMAC
=================================================================

See :issue:`106307`

Description
===========

TYPO3 now uses SHA3-256 for HMAC operations across multiple components, replacing
the previously used MD5, SHA1, and SHA256 algorithms. SHA3-256 (Keccak) produces
64-character hex hashes compared to the 32 or 40 characters of the older algorithms.

The following components have been upgraded:

==================================  ==============  ===============
Component                           Previous HMAC   New HMAC
==================================  ==============  ===============
cHash                               MD5             SHA3-256
Backend password recovery           SHA1            SHA3-256
Frontend password recovery          SHA1            SHA3-256
File dump controller                SHA1            SHA3-256
Show image controller               SHA1            SHA3-256
Backend form protection             SHA1            SHA3-256
Extbase form request attributes     SHA1            SHA3-256
Form extension request attributes   SHA1            SHA3-256
Database session backend            SHA256          SHA3-256
Redis session backend               SHA256          SHA3-256
==================================  ==============  ===============

Database fields have been extended to accommodate the longer hash values
(and would even support SHA3-512 with 128 hex characters in the future):

- `be_users.password_reset_token`: 100 → 128 characters
- `fe_users.felogin_forgotHash`: 80 → 160 characters (incl. additional timestamp details)


Impact
======

The algorithm change has the following immediate effects:

**URLs with HMAC tokens become invalid:**

- cHash parameters in frontend URLs are invalidated
- File dump URLs (file downloads) require regeneration
- Show image URLs require regeneration

**Active password reset tokens expire:**

- Backend user password reset links in progress become invalid
- Frontend user password reset links in progress become invalid
- Users must request new password reset emails

**Session handling:**

- Existing session identifiers will be regenerated on next user login
- No immediate session invalidation occurs

**Database schema:**

- Field lengths are automatically updated during upgrade
- No data migration is required for existing records


Affected installations
======================

All installations upgrading to TYPO3 v14 are affected.

The impact varies based on usage:

- **High impact**: Installations with active password reset processes or cached frontend URLs with cHash parameters
- **Medium impact**: Installations using file dump or show image controllers with externally stored URLs
- **Low impact**: All other installations (automatic migration on next use)


Migration
=========

**Database schema updates:**

Execute the database analyzer in the Install Tool or run :bash:`vendor/bin/typo3 upgrade:run`.

**URLs and caching:**

- Frontend caches should be cleared to regenerate cHash values
- File dump and show image URLs regenerate automatically on next access
- External references to file/image URLs must be updated

..  important::

    Existing links with `&cHash=` URL parameters will become invalid and respond with
    an HTTP 404 error. Search engines first need to crawl the site and discover the
    new URLs having the longer cache-hash value. This probably has an impact on SEO.

    :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['fallbackToLegacyHash'] = true;`
    can be used to still allow the legacy MD5 cache-hash during frontend requests.

**Sessions:**

No manual intervention required. Sessions are automatically rehashed on next login.

**Custom extensions:**

If custom code uses :php:`HashService::hmac()` directly, review whether the default
SHA1 algorithm is still appropriate. Consider explicitly passing :php:`HashAlgo::SHA3_256`
for new HMAC operations:

.. code-block:: php

   use TYPO3\CMS\Core\Crypto\HashAlgo;
   use TYPO3\CMS\Core\Crypto\HashService;

   $hash = $hashService->hmac($data, 'my-additional-secret', HashAlgo::SHA3_256);

..  index:: Backend, Database, Frontend, NotScanned, ext:core
