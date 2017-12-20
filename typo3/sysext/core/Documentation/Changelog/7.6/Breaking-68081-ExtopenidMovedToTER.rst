.. include:: ../../Includes.txt

==========================================
Breaking: #68081 - Ext:openid moved to TER
==========================================

See :issue:`68081`

Description
===========

The former core extension `openid` has been removed from core code
and is now available as optional extension from the TER.


Impact
======

Login to TYPO3 backend via openid fails until the extension is installed from TER.


Affected Installations
======================

Instances using backend login via openid.


Migration
=========

An upgrade wizard in the install tool can be use to download and install the extension.

.. index:: Backend, ext:openid