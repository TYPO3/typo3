..  include:: /Includes.rst.txt

..  _feature-111134-1792300001:

==================================================================
Feature: #111134 - Allow #[IgnoreValidation] on Extbase properties
==================================================================

See :issue:`111134`

Description
===========

The PHP attribute :php:`\TYPO3\CMS\Extbase\Attribute\IgnoreValidation` can now
be applied to properties of Extbase domain models, in addition to action
parameters of controller methods.

A property marked with :php:`#[IgnoreValidation]` is excluded from the
validation of its model completely: Extbase neither creates the validators it
would generate automatically for properties typed as another model or as a
collection, nor does it apply validators declared with :php:`#[Validate]` or
with Symfony constraint attributes on that property. This is the same meaning
:php:`#[IgnoreValidation]` has on action parameters.

..  code-block:: php
    :caption: EXT:my_extension/Classes/Domain/Model/Product.php

    use TYPO3\CMS\Extbase\Attribute\IgnoreValidation;

    class Product extends AbstractEntity
    {
        #[IgnoreValidation]
        protected ?Category $category = null;
    }

Excluding a property from validation is independent of its persistence.
Whether a property is persisted is decided by the TCA: Extbase only maps
properties that have a matching column. :php:`#[Transient]` is not needed for
either purpose.

Impact
======

Extension authors can exclude single model properties from validation, for
example model-typed properties without accessor methods, without having to mark
them as transient.

Extensions that support TYPO3 v13.4, v14 and v15 can carry both attributes on
a property. Extbase in TYPO3 v13.4 and v14 does not evaluate unknown property
attributes, so :php:`#[IgnoreValidation]` is simply ignored there, while
:php:`#[Transient]` keeps skipping the validator.

..  index:: PHP-API, NotScanned, ext:extbase
