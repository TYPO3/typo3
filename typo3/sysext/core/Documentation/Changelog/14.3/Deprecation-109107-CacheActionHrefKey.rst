.. include:: /Includes.rst.txt

.. _deprecation-109107-1772108218:

=============================================
Deprecation: #109107 - CacheAction key "href"
=============================================

See :issue:`109107`

Description
===========

The :php:`CacheAction` array key :php:`href` used in cache action definitions
provided via the :php:`ModifyClearCacheActionsEvent` has been deprecated in
favor of :php:`endpoint`. The new key name better reflects the purpose of
this field, which is used as an AJAX endpoint URL. The value must be a
:php:`string`.

Impact
======

Cache action arrays that contain an :php:`href` key but no :php:`endpoint` key
will trigger a PHP :php:`E_USER_DEPRECATED` notice. The
:php:`ClearCacheToolbarItem` will automatically migrate :php:`href` to
:php:`endpoint` at runtime to maintain backward compatibility.

Support for the :php:`href` key will be removed in TYPO3 v15.0.

Affected installations
======================

Any installation using extensions that register custom cache actions via
:php:`ModifyClearCacheActionsEvent` and provide the action URL under the
:php:`href` array key.

Migration
=========

Replace the :php:`href` key with :php:`endpoint` in any cache action array
returned from a :php:`ModifyClearCacheActionsEvent` listener.

.. code-block:: php

   // Before (deprecated)
   $event->addCacheAction([
       'id' => 'my_custom_cache',
       'href' => $uriBuilder->buildUriFromRoute('ajax_my_cache_clear'),
       'iconIdentifier' => 'actions-system-cache-clear',
       'title' => 'Clear my cache',
       'description' => 'Optional description',
       'severity' => 'notice',
   ]);

   // After
   $event->addCacheAction([
       'id' => 'my_custom_cache',
       'endpoint' => (string)$uriBuilder->buildUriFromRoute('ajax_my_cache_clear'),
       'iconIdentifier' => 'actions-system-cache-clear',
       'title' => 'Clear my cache',
       'description' => 'Optional description',
       'severity' => 'notice',
   ]);

.. index:: Backend, PHP-API, ext:backend, NotScanned
