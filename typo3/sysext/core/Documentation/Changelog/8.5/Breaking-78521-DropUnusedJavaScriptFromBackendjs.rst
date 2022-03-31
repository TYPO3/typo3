.. include:: /Includes.rst.txt

=========================================================
Breaking: #78521 - Drop unused JavaScript from backend.js
=========================================================

See :issue:`78521`

Description
===========

The following JavaScript methods related to ExtJS have been removed from the Backend main frame
as defined in the main :file:`backend.js` file.

* :js:`TYPO3._instances`
* :js:`TYPO3.addInstance`
* :js:`TYPO3.getInstance`
* :js:`TYPO3.helpers.split`


Impact
======

Any call to one of the above mentioned methods will result in a JavaScript error.


Affected Installations
======================

Any installation that uses one of the methods mentioned above.

.. index:: Backend, JavaScript
