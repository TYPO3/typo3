.. include:: /Includes.rst.txt

.. _feature-98921-1666766437:

=============================================================================
Feature: #98921 - Get multiple items by common key prefix from local storages
=============================================================================

See :issue:`98921`

Description
===========

A new method :js:`getByPrefix()` is added to the module
:js:`@typo3/backend/storage/abstract-client-storage`, affecting its
implementations

* :js:`@typo3/backend/storage/browser-session`
* :js:`@typo3/backend/storage/client`


Impact
======

A developer is now able to obtain multiple items prefixed by a given key either
from :js:`localStorage` or :js:`sessionStorage`.

Example:

..  code-block:: js

    import Client from '@typo3/backend/storage/client';

    Client.set('common-prefix-a', 'a');
    Client.set('common-prefix-b', 'b');
    Client.set('common-prefix-c', 'c');

    const entries = Client.getByPrefix('common-prefix-');
    // {'common-prefix-a': 'a', 'common-prefix-b': 'b', 'common-prefix-c': 'c'}


.. index:: JavaScript, ext:backend
