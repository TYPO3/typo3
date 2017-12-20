
.. include:: ../../Includes.txt

=================================================
Breaking: #60582 - Rsaauth Javascript Files Moved
=================================================

See :issue:`60582`

Description
===========

Javascript files from EXT:rsaauth were moved from resources/ to Resources/Public/JavaScript.


Impact
======

Javascript or file not found errors.


Affected installations
======================

An installation is affected if a 3rd party extension includes Javascript files from rsaauth.


Migration
=========

Change affected extension to include Javascript files from resources/ to Resources/Public/JavaScript/.


.. index:: JavaScript, ext:rsaauth
