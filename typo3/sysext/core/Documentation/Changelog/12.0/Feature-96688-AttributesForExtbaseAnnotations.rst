.. include:: /Includes.rst.txt

.. _feature-96688:

====================================================
Feature: #96688 - Attributes for Extbase Annotations
====================================================

See :issue:`96688`

Description
===========

Since PHP 8, native attributes are supported. In comparison to doc comments, attributes have auto-completion,
are better readable and were "invented" for storing meta-information about properties.
For more info on attributes see https://stitcher.io/blog/attributes-in-php-8 and https://www.php.net/manual/en/language.attributes.overview.php

Extbase annotations are already nearly 1:1 translatable to attributes.

Impact
======

In addition to their usage as annotations, the following Extbase annotations have been enriched for usage as attributes:

..  code-block:: php

    @Extbase\ORM\Transient
    @Extbase\ORM\Cascade
    @Extbase\ORM\Lazy
    @Extbase\IgnoreValidation
    @Extbase\Validate

Examples
--------

Transient & Lazy
++++++++++++++++

Annotations::

    use TYPO3\CMS\Extbase\Annotation as Extbase;

    /**
     * @Extbase\ORM\Lazy()
     * @Extbase\ORM\Transient()
     */

Attributes::

    use TYPO3\CMS\Extbase\Annotation as Extbase;

    #[Extbase\ORM\Lazy()]
    #[Extbase\ORM\Transient()]

Cascade
+++++++

Annotation::

    /**
     * @Extbase\ORM\Cascade("remove")
     */

Attribute::

    #[Extbase\ORM\Cascade(['value' => 'remove'])]

Validate
++++++++

Annotations::

    /**
     * @Extbase\Validate("StringLength", options={"minimum": 1, "maximum": 10})
     * @Extbase\Validate("NotEmpty")
     * @Extbase\Validate("TYPO3.CMS.Extbase:NotEmpty")
     * @Extbase\Validate("TYPO3.CMS.Extbase.Tests.Unit.Reflection.Fixture:DummyValidator")
     * @Extbase\Validate("\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     * @Extbase\Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     */
    protected $propertyWithValidateAnnotations;

Attributes::

    #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['minimum' => 1, 'maximum' => 10]])]
    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    #[Extbase\Validate(['validator' => 'TYPO3.CMS.Extbase:NotEmpty'])]
    #[Extbase\Validate(['validator' => 'TYPO3.CMS.Extbase.Tests.Unit.Reflection.Fixture:DummyValidator'])]
    #[Extbase\Validate(['validator' => '\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator'])]
    #[Extbase\Validate(['validator' => NotEmptyValidator::class])]
    protected $propertyWithValidateAttributes;

With promoted properties in constructor::

    public function __construct(
        #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['minimum' => 1, 'maximum' => 10]])]
        #[Extbase\Validate(['validator' => 'NotEmpty'])]
        #[Extbase\Validate(['validator' => 'TYPO3.CMS.Extbase:NotEmpty'])]
        #[Extbase\Validate(['validator' => 'TYPO3.CMS.Extbase.Tests.Unit.Reflection.Fixture:DummyValidator'])]
        #[Extbase\Validate(['validator' => '\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator'])]
        #[Extbase\Validate(['validator' => NotEmptyValidator::class])]
        public readonly string $dummyPromotedProperty
    )
    {
        // your code here
    }

.. index:: PHP-API, ext:extbase
