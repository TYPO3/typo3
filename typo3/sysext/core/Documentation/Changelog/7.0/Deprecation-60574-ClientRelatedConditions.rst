
.. include:: ../../Includes.txt

===============================================
Deprecation: #60574 - Client Related Conditions
===============================================

See :issue:`60574`

Description
===========

Conditions that depend on client details are unlovely for a number
of reasons:

* Per condition or permutation of conditions that matches, the frontend
  creates a different cache entry. This can lead to a very high number
  of cache entrys per page
* Conditions based on browser or clients on server side are bad practice.
* The device information in the core is outdated (for example it is possible to match "AMIGA")
* Setups like reverse proxies give additional headaches with these types of conditions
* All client related condition types are deprecated with this patch.


Impact
======

Usage of client related TypoScript conditions will result in a deprecation log message. Client related conditions
are browser, version, system and useragent.

Affected installations
======================

Installations using TypoScript conditions for browser, version, system or useragent.

Migration
=========
* Most usual conditions for specific browsers can nowadays be turned into conditional CSS includes
* Use libraries such as modernizr for browser support
* If conditions for specific clients or devices are still needed, they
  should be done with a userFunc condition and a project like WURFL
  that keep the device information more recent than the current core
  code like matching AMIGA


.. index:: TypoScript, Frontend
