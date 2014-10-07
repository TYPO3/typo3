===============================================================
Breaking: #61863 - deprecated connectDB from EidUtility removed
===============================================================

Description
===========

Method connectDB() from \TYPO3\CMS\Frontend\Utility\EidUtility is removed.


Impact
======

Extensions that still use the function connectDB will trigger a fatal
PHP error when an eID script is called.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed function.


Migration
=========

The function can be removed safely. The database will connect itself if needed.
