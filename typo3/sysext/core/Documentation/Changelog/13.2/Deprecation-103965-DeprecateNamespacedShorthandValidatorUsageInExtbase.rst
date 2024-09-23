.. include:: /Includes.rst.txt

.. _deprecation-103965-1717335369:

================================================================================
Deprecation: #103965 - Deprecate namespaced shorthand validator usage in Extbase
================================================================================

See :issue:`103965`

Description
===========

It is possible to use the undocumented namespaced shorthand notation in Extbase
to add validators for properties or arguments. As an example,
:php:`TYPO3.CMS.Extbase:NotEmpty` will be resolved as
:php:`TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator` or
:php:`Vendor.Extension:Custom` will be resolved as
:php:`\Vendor\MyExtension\Validation\Validator\CustomValidator`.

The namespaced shorthand notation for Extbase validators has been marked as
deprecated and will be removed in TYPO3 v14.


Impact
======

Using namespaced shorthand notation in Extbase will trigger a PHP deprecation
warning.


Affected installations
======================

All installations using namespaced shorthand notation in Extbase are affected.


Migration
=========

Extensions using the namespaced shorthand notation must use the FQCN of the
validator instead. In case of Extbase core validators, the well known
shorthand validator name can be used.

Before
------

..  code-block:: php

    /**
     * @Extbase\Validate("TYPO3.CMS.Extbase:NotEmpty")
     */
    protected $myProperty1;

    /**
     * @Extbase\Validate("Vendor.Extension:Custom")
     */
    protected $myProperty2;


After
-----

..  code-block:: php

    /**
     * @Extbase\Validate("NotEmpty")
     */
    protected $myProperty1;

    /**
     * @Extbase\Validate("Vendor\Extension\Validation\Validator\CustomValidator")
     */
    protected $myProperty2;

or

..  code-block:: php

    #[Extbase\Validate(['validator' => \TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator::class])]
    protected $myProperty1;

    #[Extbase\Validate(['validator' => \Vendor\Extension\Validation\Validator\CustomValidator::class])]
    protected $myProperty2;

.. index:: Frontend, NotScanned, ext:extbase
