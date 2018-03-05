.. include:: ../../Includes.txt

====================================================================================
Important: #83971 - Browser Notification API only works on SSL encrypted connections
====================================================================================

See :issue:`83971`

Description
===========

Google Chrome has deprecated the JavaScript Notification API for unencrypted connections, which
triggers warnings. Due to this TYPO3 does not use the LoginRefresh notification here anymore.

See https://goo.gl/rStTGz for more details.


Impact
======

The browser notifications for expired login only works on HTTPS.


Affected Installations
======================

Any installation which does not use HTTPS.


Migration
=========

Use SSL / HTTPS for the installation and the Notification API will work just like before.


.. index:: Backend, JavaScript
