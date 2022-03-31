
.. include:: /Includes.rst.txt

==========================================================
Breaking: #62914 - Early check for PHP 5.5 in Install Tool
==========================================================

See :issue:`62914`

Description
===========

PHP 5.5 or above is a requirement for TYPO3 CMS7. As code is using specific PHP 5.5 features, an
early check is required in Install Tool.


Impact
======

Install Tool will throw an exception if PHP 5.5 or above is not detected.


Affected installations
======================

Any installation without at least PHP 5.5.0.


Migration
=========

Upgrade to PHP 5.5 or above.


.. index:: PHP-API
