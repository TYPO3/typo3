.. include:: /Includes.rst.txt

.. _feature-104990-1726495719:

===================================================
Feature: #104990 - Automatic frontend cache tagging
===================================================

See :issue:`104990`

Description
===========

When database records are used in the frontend, and the rendered result is put
into caches like the page cache, the TYPO3 frontend now automatically tags cache
entries with lists of used records.

When changing such records in the backend, affected cache entries are dropped,
leading to automatic cache eviction.

This is a huge improvement to previous TYPO3 versions where tagging and cache
eviction had to configured manually.

This feature - automatically tagging cache entries - is the final solution to
consistent caches at any point in time. It is however a bit tricky to get right
in a performant way: There are still details to rule out, and the core will
continue to improve in this area. The basic implementation in TYPO3 v13 however
already resolves many use cases. Core development now goes ahead to see how this
features behaves in the wild.

This feature is encapsulated in the feature toggle :php:`frontend.cache.autoTagging`:
It is enabled by default with new instances based on TYPO3 v13, and needs to be
manually enabled for instances being upgrades from previous versions.


Impact
======

Instances configured with the feature toggle automatically tag caches. Affected
cache entries will be removed when changing records.


.. index:: Frontend, ext:core
