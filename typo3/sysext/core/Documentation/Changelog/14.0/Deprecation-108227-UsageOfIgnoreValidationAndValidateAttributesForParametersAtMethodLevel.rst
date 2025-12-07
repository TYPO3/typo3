..  include:: /Includes.rst.txt

..  _deprecation-108227-1763668119:

===========================================================================================================================
Deprecation: #108227 - Usage of :php:`#[IgnoreValidation]` and :php:`#[Validate]` attributes for parameters at method level
===========================================================================================================================

See :issue:`108227`

Description
===========

Usage of the following extbase attribute properties is deprecated since TYPO3
v14.0:

*   :php:`$argumentName` property of :php:`#[IgnoreValidation]` attribute
*   :php:`$param` property of :php:`#[Validate]` attribute

Instead of using these properties, the containing attributes should be placed
directly at the appropriate method parameters.

**Example:**

..  code-block:: php

    final class FooController extends ActionController
    {
        public function barAction(
            #[IgnoreValidation]
            string $something,
        ): ResponseInterface {
            // Do something...
        }

        public function bazAction(
            #[Validate(validator: 'NotEmpty')]
            string $anythingNotEmpty,
        ): ResponseInterface {
            // Do something...
        }
    }

..  note::
    It is still possible to place these attributes at controller methods in
    order to apply the validation behavior for a whole method instead of a
    single parameter. In addition, the :php:`#[Validate]` attribute may still
    be used for single properties.

    The mentioned deprecation affects only the usage of attributes to
    **control parameter validation handling**. All other validation behaviors
    remain unchanged.


Impact
======

Passing a value other than :php:`null` to the mentioned attribute parameters
will trigger a deprecation warning. Validation will still work as expected, but
will stop working with TYPO3 v15.


Affected installations
======================

All installations using the :php:`#[IgnoreValidation]` and :php:`#[Validate]`
attributes in Extbase context in combination with the mentioned attribute
parameters are affected.


..  _deprecation-108227-1763668119-migration:

Migration
=========

Developers can easily migrate their implementations by moving parameter-related
attributes next to the method parameters instead of the related method.

**Before:**

..  code-block:: php

    final class FooController extends ActionController
    {
        #[IgnoreValidation(argumentName: 'something')]
        public function barAction(string $something): ResponseInterface
        {
            // Do something...
        }

        #[Validate(validator: 'NotEmpty', param: 'anythingNotEmpty')]
        public function bazAction(string $anythingNotEmpty): ResponseInterface
        {
            // Do something...
        }
    }

**After:**

..  code-block:: php

    final class FooController extends ActionController
    {
        public function barAction(
            #[IgnoreValidation]
            string $something,
        ): ResponseInterface {
            // Do something...
        }

        public function bazAction(
            #[Validate(validator: 'NotEmpty')]
            string $anythingNotEmpty,
        ): ResponseInterface {
            // Do something...
        }
    }

**Combined diff:**

..  code-block:: diff

     final class FooController extends ActionController
     {
    -    #[IgnoreValidation(argumentName: 'something')]
    -    public function barAction(string $something): ResponseInterface
    -    {
    +    public function barAction(
    +        #[IgnoreValidation]
    +        string $something,
    +    ): ResponseInterface {
             // Do something...
         }


    -    #[Validate(validator: 'NotEmpty', param: 'anythingNotEmpty')]
    -    public function bazAction(string $anythingNotEmpty): ResponseInterface
    -    {
    +    public function bazAction(
    +        #[Validate(validator: 'NotEmpty')]
    +        string $anythingNotEmpty,
    +    ): ResponseInterface {
             // Do something...
         }
     }

..  index:: Frontend, PHP-API, NotScanned, ext:extbase
