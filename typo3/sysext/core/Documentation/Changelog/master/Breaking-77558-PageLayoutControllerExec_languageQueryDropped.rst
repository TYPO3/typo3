=======================================================
Breaking: #77558 - PageLayoutController removed methods
=======================================================

Description
===========

The following methods have been removed from :php:``PageLayoutController`` without substitution:

* :php:``exec_languageQuery``
* :php:``isColumnEmpty``
* :php:``getElementsFromColumnAndLanguage``

All of those methods were internally used within Page module, the risk an extension uses them is low.

Impact
======

The methods executed page module specific queries. Extensions calling the method will throw a fatal error.


Affected Installations
======================

Extensions calling one of the aforementioned methods.


Migration
=========

Move away from those methods.