
.. include:: ../../Includes.txt

========================================
Feature: #64031 - JavaScript Storage API
========================================

See :issue:`64031`

Description
===========

Accessing the Backend User configuration ($BE_USER->uc) can be handled in JavaScript with a common and simple
key-value storage manner, allowing to store any data. Additionally, the use of HTML5s localStorage allows to
store any data in the same way inside the browser. All localStorage data is prefixed with "t3-" in order to avoid
collisions with other data from the same browserStorage.

Impact
======

API Methods
-----------

The API provides two objects available in the top frame attached to the global TYPO3 object:

1) `top.TYPO3.Storage.Client`
2) `top.TYPO3.Storage.Persistent`

Each object has the following API methods

* `get(key)` To fetch the data behind the key.
* `set(key, value)` To set/override a key with any arbitrary content.
* `isset(key)` (bool) checks if the key is in use.
* `clear()` to empty all data inside the storage.


Examples
--------

To fetch data from the persistent user configuration, simple use the key known already:

.. code-block:: javascript

	top.TYPO3.Storage.Persistent.get('startModule');

Storing / Updating data in the storage works like this, and can contain any data type.

.. code-block:: javascript

	top.TYPO3.Storage.Persistent.set('startModule', 'web_info');

The same is possible for browserStorage using top.TYPO3.Storage.Client.


.. index:: JavaScript, Backend
