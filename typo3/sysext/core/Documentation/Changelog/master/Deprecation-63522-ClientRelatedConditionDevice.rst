=================================================================
Deprecation: #63522 - Deprecate the "device" TypoScript condition
=================================================================

Description
===========

Next other client related TypoScript conditions the "device" type
condition is deprecated.


Impact
======

Using a condition like ``[device = wap]`` is considered outdated
and should be solved differently.

Affected installations
======================

Instances with TypoScript that relies on ``[device = ...]``.

Migration
=========

* Most usual conditions for specific browsers can nowadays be turned into conditional CSS includes
* Use libraries such as modernizr for browser support
* If conditions for specific clients or devices are still needed, they
  should be done with a userFunc condition and a project like WURFL
  that keep the device information more recent than the current core
  code like matching AMIGA
