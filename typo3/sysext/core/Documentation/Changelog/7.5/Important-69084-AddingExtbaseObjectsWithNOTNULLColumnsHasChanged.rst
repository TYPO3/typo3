
.. include:: ../../Includes.txt

============================================================================
Important: #69084 - Adding Extbase Objects with NOT NULL columns has changed
============================================================================

See :issue:`69084`

Description
===========

To better support databases that don't silently convert `NULL` values to
an empty default value for database columns defined as `NOT NULL` the
`insertObject()` method tries to determine the appropriate value for a column.

Extbase object properties that have a value of `NULL` will be skipped when
preparing the record to enable the DBMS default value to be used. This behavior
has not changed compared to TYPO3 CMS 7.4 but allows proper support for DBMS that
are strict about `NOT NULL` columns by defining appropriate default values for
properties in the models.


Example database schema:

.. code-block:: sql

	CREATE TABLE tx_blogexample_domain_model_blog (
		title varchar(255) DEFAULT '' NOT NULL
	);

Example model definition:

.. code-block:: php

	class Blog extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {
		/**
		 * The blog's title.
		 *
		 * @var string
		 */
		protected $title = '';
	}


.. index:: Database, ext:extbase
