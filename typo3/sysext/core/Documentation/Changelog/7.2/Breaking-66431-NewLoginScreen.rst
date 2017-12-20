
.. include:: ../../Includes.txt

===================================
Breaking: #66431 - New Login Screen
===================================

See :issue:`66431`

Description
===========

To keep the focus on the most recent news, the news are reduced to a single view
carousel where you can slide through the latest news.

A title for the news section is not needed anymore, in result
$GLOBALS['TYPO3_CONF_VARS']['BE']['loginNewsTitle'] is superfluous and has
been removed completely without replacement.


Impact
======

The news section title has been dropped without replacement.
There will be no alternative section title displayed.


Affected Installations
======================

Installations that used $GLOBALS['TYPO3_CONF_VARS']['BE']['loginNewsTitle'] to
set an alternative section title for the news.


Migration
=========

If an entry for BE/loginNewsTitle exists in your local configuration it will be
removed by the SilentConfigurationUpgradeService automatically.


.. index:: PHP-API, Backend, LocalConfiguration
