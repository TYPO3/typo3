..  include:: /Includes.rst.txt

..  _breaking-107315-1755627000:

==============================================================
Breaking: #107315 - Cache Backend and Frontend related changes
==============================================================

See :issue:`107315`

Description
===========

In TYPO3 v14, the PHP code for the Cache Backend and Cache Frontend has undergone
some major changes, which might affect extensions:

- Most PHP code from Cache Backends and Cache Frontends are now strongly typed
  by PHP native typing system
- Method signatures now use proper type declarations for parameters and return types
- The `mixed` type is now used where appropriate for flexible data handling
- All string parameters and boolean return types are properly declared

The following interfaces have been updated with strict typing:

- :php:`\TYPO3\CMS\Core\Cache\Backend\BackendInterface`
- :php:`\TYPO3\CMS\Core\Cache\Backend\PhpCapableBackendInterface`
- :php:`\TYPO3\CMS\Core\Cache\Backend\TransientBackendInterface`
- :php:`\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface`

Additionally, the abstract base class constructor backward compatibility was removed:

- :php:`\TYPO3\CMS\Core\Cache\Backend\AbstractBackend`

Impact
======

If you are using or extending the Cache Backend or Cache Frontend, you need to ensure
that you are using the correct PHP types. This includes method parameters, return types,
and property types in your classes.


Affected installations
======================

All installations that extend or implement cache backend or frontend classes are affected.
This includes custom cache implementations in third-party extensions.

Extension authors who have created custom cache backends or frontends will need to update
their class method signatures to match the new type declarations from interfaces.

Migration
=========

Update your custom cache backend and frontend implementations to use the correct PHP types:

1. Ensure all method signatures match the interface declarations exactly
2. Add proper type hints for parameters (e.g., :php:`string $entryIdentifier`)
3. Add proper return type declarations (e.g., :php:`bool`, :php:`mixed`, :php:`void`)
4. Update any extending classes to use the same type declarations

Example migration for a custom backend:

.. code-block:: php

   // Before (TYPO3 v13)
   class MyCustomBackend implements BackendInterface
   {
       public function get($entryIdentifier)
       {
           // implementation
       }

       public function has($entryIdentifier)
       {
           // implementation
       }
   }

   // After (TYPO3 v14)
   class MyCustomBackend implements BackendInterface
   {
       public function get(string $entryIdentifier): mixed
       {
           // implementation
       }

       public function has(string $entryIdentifier): bool
       {
           // implementation
       }
   }

Example migration for a custom backend extending AbstractBackend:

.. code-block:: php

   // Before (TYPO3 v13)
   class MyCustomBackend implements AbstractBackend
   {
       public function __construct($context, array $options = [])
       {
             parent::__construct($context, $options);
       }
   }

   // After (TYPO3 v14)
   class MyCustomBackend implements AbstractBackend
   {
       public function __construct(array $options = [])
       {
           parent::__construct($options);
       }
   }

Note for extensions that strive for TYPO3 v13 and v14 compatibility: The
:php:`__construct()` change of :php:`AbstractBackend` could be mitigated
by omitting a type for the first argument and checking whether its incoming
value is a string (v13), or an array (v14).

For interface changes that added types to method argument signatures, implementing
services could omit the type to keep backwards compatibility with TYPO3 v13. For
added return value types, they must be added for v14 compatibility, and v13 should
be fine with that. All in all, it *should* be possible to have only one implementing
class for both v13 and v14, but it may be a bit tricky. Codewise it will be more
easy to have dedicated classes.


..  index:: PHP-API, NotScanned, ext:core
