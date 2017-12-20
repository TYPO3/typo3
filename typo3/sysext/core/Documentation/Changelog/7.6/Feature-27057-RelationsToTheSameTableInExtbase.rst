
.. include:: ../../Includes.txt

========================================================
Feature: #27057 - Relations to the same table in Extbase
========================================================

See :issue:`27057`

Description
===========

It is now possible to use a domain model where an object is connected to another object of the same class directly

.. code-block:: php

	namespace \Vendor\Extension\Domain\Model;
	class A {
		/**
		* @var \Vendor\Extension\Domain\Model\A
		*/
		protected $parent;

as well as using a domain model where an object has multiple relations to objects of the same class

.. code-block:: php

	namespace \Vendor\Extension\Domain\Model;
	class A {
		/**
		* @var \Vendor\Extension\Domain\Model\B
		*/
		protected $x;

		/**
		* @var \Vendor\Extension\Domain\Model\B
		*/
		protected $y;

as well as indirectly

.. code-block:: php

	namespace \Vendor\Extension\Domain\Model;
	class A {
		/**
		* @var \Vendor\Extension\Domain\Model\B
		*/
		protected $b;

		/**
		* @var \Vendor\Extension\Domain\Model\C
		*/
		protected $c;

	namespace \Vendor\Extension\Domain\Model;
	class B {
		/**
		* @var \Vendor\Extension\Domain\Model\C
		*/
		protected $c;

Using this kind of relations before was only possible by overriding the Extbase query builder and doing manual queries because the Extbase query builder created wrong SQL statements. Now Extbase properly supports these cases.


Impact
======

Extbase now correctly handles relations to objects of the same class.


.. index:: PHP-API, ext:extbase
