.. include:: /Includes.rst.txt

======================================================
Feature: #91738 - Introduce wrapper for sessionStorage
======================================================

See :issue:`91738`

Description
===========

TYPO3 now ships a new module acting as wrapper for :js:`sessionStorage`. It
behaves similar to :js:`localStorage`, except that the stored data is dropped
after the browser session has ended.


Impact
======

The module :js:`TYPO3/CMS/Core/Storage/BrowserSession` is available to be used
to store data in the :js:`sessionStorage`.

API Methods
-----------

* `get(key)` To fetch the data behind the key.
* `set(key, value)` To set/override a key with any arbitrary content.
* `isset(key)` (bool) checks if the key is in use.
* `unset(key)` To remove a key from the storage.
* `clear()` to empty all data inside the storage.
* `unsetByPrefix(prefix)` to empty all data inside the storage with their keys starting with a prefix

.. index:: JavaScript, ext:core
