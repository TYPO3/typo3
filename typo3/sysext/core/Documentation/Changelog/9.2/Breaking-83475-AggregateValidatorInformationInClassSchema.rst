.. include:: /Includes.rst.txt

==================================================================
Breaking: #83475 - Aggregate validator information in class schema
==================================================================

See :issue:`83475`

Description
===========

It is no longer possible to use the following semantic sugar to define validators for properties of action parameters:

.. code-block:: php

	/*
	 * @param Model $model
	 * @validate $model.property NotEmpty
	 */
	public function foo(Model $model){}

Mind the dot and the reference to the property. This will no longer work.
Of course, the regular validation of action parameters stays intact.

.. code-block:: php

	/*
	 * @param Model $model
	 * @validate $model CustomValidator
	 */
	public function foo(Model $model){}

This will continue to work.


Impact
======

If you rely on that feature, you need to manually implement the validation in the future.


Affected Installations
======================

All installations that use that feature.


Migration
=========

If you used that feature for adding validators to models, you can define the validators inside the model instead or
inside a model validator, that is automatically registered and loaded if defined.

When using that feature with regular objects, you need to write custom validators and call the desired property
validators in there.

.. index:: ext:extbase, PHP-API, NotScanned
