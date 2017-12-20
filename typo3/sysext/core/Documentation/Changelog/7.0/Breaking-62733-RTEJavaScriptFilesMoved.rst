
.. include:: ../../Includes.txt

=============================================
Breaking: #62733 - RTE Javascript Files Moved
=============================================

See :issue:`62733`

Description
===========

Javascript files of the rtehtmlarea extension were moved from EXT:rtehtmlarea/htmlarea/ to
EXT:rtehtmlarea/Resources/Public/JavaScript/


Impact
======

Javascript or file not found errors.


Affected installations
======================

An installation is affected if a 3rd party extension loads any JavaScript file from EXT:rtehtmlarea/htmlarea/


Migration
=========

Any affected 3rd party extension must be modified to load any JavaScript file from EXT:rtehtmlarea/Resources/Public/JavaScript/ instead.


.. index:: JavaScript, RTE, Backend
