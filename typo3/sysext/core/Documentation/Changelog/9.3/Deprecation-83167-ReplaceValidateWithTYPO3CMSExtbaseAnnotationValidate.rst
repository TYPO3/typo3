.. include:: /Includes.rst.txt

=======================================================================================
Deprecation: #83167 - Replace @validate with @TYPO3\\CMS\\Extbase\\Annotation\\Validate
=======================================================================================

See :issue:`83167`

Description
===========

The :php:`@validate` annotation has been marked as deprecated and should be replaced with the doctrine annotation
:php:`@TYPO3\CMS\Extbase\Annotation\Validate`.


Impact
======

Classes using :php:`@validate` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All extensions that use :php:`@validate`


Migration
=========

Use :php:`@TYPO3\CMS\Extbase\Annotation\Validate` instead.


Examples:
---------

The following examples show both the old and the new way of using validation annotations. Both versions can still be used
in the same doc block, but you should start using the new way today.

.. code-block:: php

	use TYPO3\CMS\Extbase\Annotation as Extbase;


.. note::

   Doctrine annotations are actual classes, so they can be either used via FQCN, imported via the use statement or even
   be aliased which is the preferred way. As doctrine annotations can only be used in the Extbase context (for now), the
   aliased version makes that perfectly clear even for people that are new to TYPO3.

.. tip::

   When using PhpStorm, you can install the `PHP Annotation` plugin that recognizes the annotation classes and makes you
   jump directly into them. Also, it enables autocompletion for annotation options.

Validators for class properties
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is how annotations look like, that register validators without options

.. code-block:: php

	/**
	 * @validate NotEmpty
	 * @Extbase\Validate("NotEmpty")
	 * @var Foo
	 */
	public $property;

.. code-block:: php

	/**
	 * @validate NotEmpty
	 * @Extbase\Validate(validator="NotEmpty")
	 * @var Foo
	 */
	public $property;

This is how annotations look like, that register validators with options

.. code-block:: php

	/**
	 * @validate StringLength(minimum=3, maximum=50)
	 * @Extbase\Validate("StringLength", options={"minimum": 3, "maximum": 50})
	 * @var Foo
	 */
	public $property;

.. important::

   Registering multiple validators, separated by comma, is not possible any more. Instead, use one validator per line.

.. code-block:: php

	/**
	 * @validate StringLength(minimum=3), StringLength(maximum=50)
	 * @Extbase\Validate("StringLength", options={"minimum": 3})
	 * @Extbase\Validate("StringLength", options={"maximum": 50})
	 * @var Foo
	 */
	public $property;

Validators for method params
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. important::

   When using validators for method params, you need to define what param the validator is registered for. Also, please
   note that the param name does no longer include the dollar sign.

.. code-block:: php

	/**
	 * @validate $bar NotEmpty
	 * @Extbase\Validate("NotEmpty", param="bar")
	 * @var string $foo
	 * @var string $bar
	 */
	public function method(string $foo, string $bar)
	{
	}

Full qualified validator class names and aliases
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Of course it's still possible to reference validators by extension key, aliases and FQCN's.

.. code-block:: php

	/**
	 * @Extbase\Validate("NotEmpty")
	 * @Extbase\Validate("TYPO3.CMS.Extbase:NotEmpty")
	 * @Extbase\Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
	 * @Extbase\Validate("\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
	 */
	protected $property;

.. index:: PHP-API, ext:extbase, FullyScanned
