
.. include:: ../../Includes.txt

================================================
Breaking: #70229 - BE-lockSSL = 3 option removed
================================================

See :issue:`70229`

Description
===========

The global option `$TYPO3_CONF_VARS[BE][lockSSL]` allows to lock the backend usage to be worked completely over SSL.
Setting this option to "3" allowed to have only the backend login transmitted via SSL, but the rest forced to work
via plain HTTP. Option "3" has been removed in favor of having a full SSL session for all communication between the
server and the client / browser.


Impact
======

Installations having `lockSSL` set to "3" will now behave just as it would be lockSSL=1.


Affected Installations
======================

Any installation that has `$TYPO3_CONF_VARS[BE][lockSSL]` set to 3, only having SSL for the Backend login page.


Migration
=========

It is recommended to set the `$TYPO3_CONF_VARS[BE][lockSSL]` option to 1 or 2, depending on the environment and the
possibilities of having SSL available.


.. index:: LocalConfiguration, Backend
