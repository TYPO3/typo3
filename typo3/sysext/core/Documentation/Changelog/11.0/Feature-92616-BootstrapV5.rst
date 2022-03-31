.. include:: /Includes.rst.txt

==============================
Feature: #92616 - Bootstrap v5
==============================

See :issue:`92616`

Description
===========

TYPO3s Backend is powered with Bootstrap v5 (https://getbootstrap.com).

Previous versions ran with Bootstrap v3. A lots of changes and
improvements are included, especially regarding simplification
of building Layouts and Containers.

Backend modules which relied on Twitter Bootstrap v3 markup,
or functionality in JavaScript, will have a slightly different
and improved look & feel.


Impact
======

It is important during updates of custom backend modules
to study the Bootstrap Migration guidelines and adapt accordingly:

* https://getbootstrap.com/docs/4.5/migration/
* https://getbootstrap.com/docs/5.0/migration/

TYPO3 Core still includes some legacy styling and functionality
to provide compatibility, but backend markup and code will
be adapted during further TYPO3 v11 development to simplify
the HTML and CSS code shipped with TYPO3 Core.

.. index:: Backend, ext:backend
