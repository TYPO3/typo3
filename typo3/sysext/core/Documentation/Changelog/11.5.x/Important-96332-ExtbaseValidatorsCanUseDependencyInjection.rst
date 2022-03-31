.. include:: /Includes.rst.txt

===================================================================
Important: #96332 - Extbase Validators can use dependency injection
===================================================================

See :issue:`96332`

Description
===========

In contrast to what has been outlined with :doc:`this changelog <../11.0/Breaking-92238-ServiceInjectionInExtbaseValidators>`,
Extbase validators can use dependency injection in v11 again.

Using dependency injection in Extbase validators is possible again, and it
will be available as standard functionality in TYPO3 v12.

All options below are only needed for extensions that really need to find fully compatible
ways for dependency injection in their validators. In case single extensions have already been adapted
to use the strategy from :doc:`the breaking changelog <../11.0/Breaking-92238-ServiceInjectionInExtbaseValidators>`,
no further adaption is needed.

Extensions still have to apply some manual code changes to single validators
if they should be dependency injection aware, though: Validators that implement
method :php:`setOptions()` can use :php:`__construct()` or :php:`inject*` methods
for dependency injection. Method :php:`setOptions()` will be added to :php:`ValidatorInterface`
in v12 as mandatory method, and :php:`AbstractValidator` will implement it. Extensions
with dependency injection-aware validators additionally need to set the class
:yaml:`public: true` and :yaml:`shared: false` in :file:`Services.yaml`. This
will be done automatically in v12.

.. note::

    All standard validators of EXT:extbase and EXT:form will be marked :php:`final` in TYPO3 v12.
    Extension authors should consider this when refactoring validators in TYPO3 v11 already.

A typical Extbase validator that uses dependency injection in v10 and extends :php:`AbstractValidator`
looks like this in v10:

.. code-block:: php

    class MyCustomValidator extends AbstractValidator
    {
        public function injectSomething(Something $something)
        {
            $this->something = $something;
        }
    }

An extension that keeps dependency injection in v11 can now look like this:

.. code-block:: php

    class MyCustomValidator extends AbstractValidator
    {
        public function injectSomething(Something $something)
        {
            $this->something = $something;
        }

        public function setOptions(array $options): void
        {
            // This method is upwards compatible with TYPO3 v12, it will be implemented
            // by AbstractValidator in v12 directly and is part of v12 ValidatorInterface.
            // @todo: Remove this method when v11 compatibility is dropped.
            $this->initializeDefaultOptions($options);
        }
    }

An extension that keeps compatibility with v10 and v11 at the same time and needs
dependency injection for custom validators, may need an additional quirk to retain v10
compatibility. It looks like this:

.. code-block:: php

    class MyCustomValidator extends AbstractValidator
    {
        public function injectSomething(Something $something)
        {
            $this->something = $something;
        }

        public function __construct(array $options = []) {
            // Retain v10 compatibility if the validator has options. This is
            // especially important if there are *mandatory* options, otherwise
            // option initialization will be called twice in v11, which may fail.
            // @todo: Remove this method when v10 compatibility is dropped.
            if ((new Typo3Version())->getMajorVersion() < 11) {
                parent::__construct($options);
            }
        }

        public function setOptions(array $options): void
        {
            // This method is upwards compatible with TYPO3 v12, it will be implemented
            // by AbstractValidator in v12 directly and is part of v12 ValidatorInterface.
            // @todo: Remove this method when v11 compatibility is dropped.
            $this->initializeDefaultOptions($options);
        }
    }

Extensions compatible with v11 and v12 can streamline the code like this:

.. code-block:: php

    class MyCustomValidator extends AbstractValidator
    {
        public function __construct(Something $something) {
            $this->something = $something;
        }

        public function setOptions(array $options): void
        {
            // @todo: Remove this method when v11 compatibility is dropped.
            $this->initializeDefaultOptions($options);
        }
    }

The v12 and above version of this validator can then looks like this:

.. code-block:: php

    class MyCustomValidator extends AbstractValidator
    {
        public function __construct(private readonly Something $something) {
        }
    }

In all of the above cases, whenever Extbase validators need native dependency injection
without manual :php:`GeneralUtility::makeInstance()` calls for their dependencies, and
if TYPO3 v11 should be supported, these validators must set :yaml:`public: true` and
:yaml:`shared: false` in :file:`Services.yaml`:

.. code-block:: yaml

    # This is obsolete when the extension does not support TYPO3 v11 anymore.
    # @todo: Remove this when v11 compatibility is dropped.
    MyVendor\MyExtension\Validation\Validator\MyCustomValidator:
        public: true
        shared: false


.. index:: PHP-API, ext:extbase
