.. include:: /Includes.rst.txt

.. _breaking-96998:

======================================================
Breaking: #96998 - Extbase validator interface changed
======================================================

See :issue:`96998`

Description
===========

The Extbase related interface :php:`TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface`
has been changed by requiring :php:`setOptions()` method and being more strict in general.

Additionally, :php:`TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator` signatures
have been hardened.

Furthermore, all default validators delivered by EXT:extbase and EXT:form are declared final.

Following this, the framework no longer hands over :php:`options` array as constructor
argument, and no abstract implements :php:`__construct()` anymore. Classes that implement
:php:`ValidatorInterface` are automatically set "public" and "not-shared" by the framework,
they do not need to set this themselves. See the
:doc:`preparation in TYPO3 v11 <../11.5.x/Important-96332-ExtbaseValidatorsCanUseDependencyInjection>`
for more details on this. As a result, Extbase validators can now use dependency injection.

Impact
======

This has impact on custom Extbase validators which *may* need to adapt their method signatures.
Extensions that don't follow this in TYPO3 v12 may trigger fatal PHP errors.

Affected Installations
======================

Extensions with custom validators may be affected. In general, all extension classes that
directly implement :php:`ValidatorInterface` or extend :php:`AbstractValidator` may be
affected. The extension scanner can not find affected extensions, but IDE's should
show violating classes.

Migration
=========

The most casual case is that custom extension validators simply extend :php:`AbstractValidator`.
Those just have to adjust their :php:`isValid()` method signature to :php:`isValid($value): void` to
keep TYPO3 v11 & v12 compatibility. Read on for rare cases where this is not sufficient.

First, it is no longer allowed to extend specific validators of EXT:extbase and EXT:form.
Those are "leaf" classes, and extensions should not extend them, giving the Core more
freedom to change those classes if needed. Extensions should instead extend the provided
abstract classes like :php:`AbstractValidator` to implement own validators.

Since most custom validators inherit :php:`AbstractValidator`, the most important change
for these validator is a return type change of :php:`isValid()`:

..  code-block:: php

    public function isValid(mixed $value): void

Extensions that need to stay compatible with v11 (PHP 7.4) and v12, will thus typically
use a signature like below: Set the return type constraint, but omit the 'mixed' argument type:

..  code-block:: php

    public function isValid($value): void

With a closer look at the :php:`ValidatorInterface`, the v11 version
effectively looks like this:

..  code-block:: php

    interface ValidatorInterface
    {
        public function validate($value);
        public function getOptions();
    }

This has been changed in v12 to this:

..  code-block:: php

    interface ValidatorInterface
    {
        public function validate(mixed $value): Result;
        public function setOptions(array $options): void;
        public function getOptions(): array;
    }

In any case, custom validators must implement :php:`setOptions()` now. The
:php:`AbstractValidator` does that automatically, so this has little impact since
most custom validators will extend :php:`AbstractValidator` anyways.

Extensions tailored for TYPO3 v12 and above simply implement these. Extensions that
need to keep compatibility with v11 and v12 need to adjust some additional type juggling.
In general, implementing classes can *relax* method argument types (e.g. avoid :php:`mixed`
to stay PHP 7.4 compatible), but *must follow* more restricted return type constraints of
younger interfaces.

A v11 & v12 compatible method signature looks like this (avoiding the :php:`mixed` keyword
on :php:`validate`):

..  code-block:: php

    class MyValidator implements ValidatorInterface
    {
        public function setOptions(array $options): void
        {
            // ...
        }

        public function validate($value): Result
        {
            // ...
        }

        public function getOptions(): array
        {
            return $this->options;
        }
    }

.. index:: PHP-API, NotScanned, ext:extbase, ext:form
