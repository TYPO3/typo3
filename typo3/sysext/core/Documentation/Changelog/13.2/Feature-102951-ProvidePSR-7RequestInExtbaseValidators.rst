.. include:: /Includes.rst.txt

.. _feature-102951-1709643048:

==============================================================
Feature: #102951 - Provide PSR-7 request in Extbase validators
==============================================================

See :issue:`102951`

Description
===========

Extbase :php:`abstractValidator` now provides a getter and a setter
for the PSR-7 Request object. Validators extending :php:`AbstractValidator`
will include the PSR-7 request object, if the validator has been instantiated
by Extbase :php:`ValidationResolver`.


Impact
======

Extension developers can now create custom validators which consume data
from the PSR-7 request object (e.g. request attribute
:php:`frontend.user`).

.. index:: PHP-API, ext:extbase
