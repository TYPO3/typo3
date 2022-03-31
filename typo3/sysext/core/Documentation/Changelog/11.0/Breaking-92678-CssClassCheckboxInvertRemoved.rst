.. include:: /Includes.rst.txt

====================================================
Breaking: #92678 - CSS class checkbox-invert removed
====================================================

See :issue:`92678`

Description
===========

FormEngine used to have a class `checkbox-invert` for the styling
of an item with enabled flag `invertStateDisplay`. Now the checkbox value
itself is inverted. Therefore the class has been removed as it is not needed
any more.


Impact
======

Using the class doesn't have any effect on styling anymore.


Affected Installations
======================

Standard installations of TYPO3 are not affected. Only installations that
use the class `checkbox-invert` for customizations are affected.


Migration
=========

There is no migration required if only the invertStateDisplay configuration
is used. If CSS styling or JavaScript in the backend relies on the
class `checkbox-invert` present custom code needs to be added to make it
available again.

.. index:: Backend, CSS, NotScanned, ext:backend
