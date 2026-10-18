..  include:: /Includes.rst.txt

..  _deprecation-111134-1792300002:

===========================================================
Deprecation: #111134 - #[Transient] for skipping validation
===========================================================

See :issue:`111134`

Description
===========

Since :issue:`91727`, the Extbase attribute
:php:`\TYPO3\CMS\Extbase\Attribute\ORM\Transient` also skips the validator that
Extbase generates automatically for properties typed as another model or as a
collection. This coupled validation to a persistence attribute.

Relying on :php:`#[Transient]` to skip validation of such a property has been
marked as deprecated and will be removed in TYPO3 v16.0. The new attribute
:php:`\TYPO3\CMS\Extbase\Attribute\IgnoreValidation` can be used on properties
instead, see :ref:`feature-111134-1792300001`.

Impact
======

Extbase triggers a PHP deprecation warning when it builds the validators of a
model that has a property with :php:`#[Transient]` and no
:php:`#[IgnoreValidation]`, and Extbase would otherwise generate a validator
for that property. This applies to properties typed as another model and to
typed collections such as :php:`ObjectStorage<Category>`. The validator is
still skipped until TYPO3 v16.0. Starting with TYPO3 v16.0, :php:`#[Transient]`
does not influence validation anymore and the automatically generated validator
is applied.

Properties for which Extbase never generates a validator, for example
properties of simple types or of singleton classes, are not affected and
trigger no deprecation.

Properties typed as other classes, for example :php:`\DateTimeImmutable`,
trigger the deprecation as well, although the validator generated for them is
empty. Adding :php:`#[IgnoreValidation]` to such a property silences the
deprecation and changes nothing else.

Affected installations
======================

TYPO3 installations with extensions that have domain model properties with
:php:`#[Transient]` that are typed as another model or as a typed collection,
for example :php:`ObjectStorage<Category>`, are affected. The extension scanner
cannot detect this, since the deprecated behavior is the side effect of an
attribute.

Migration
=========

Replace :php:`#[Transient]` with :php:`#[IgnoreValidation]` on every property
that should not be validated. :php:`#[Transient]` is not needed to keep a
property out of persistence: Extbase only maps properties that have a matching
column in the TCA. This can be automated with a Rector rule that replaces
:php:`#[Transient]` with :php:`#[IgnoreValidation]` on every property with a
non-simple type.

..  code-block:: php
    :caption: Before (deprecated)

    use TYPO3\CMS\Extbase\Attribute\ORM\Transient;

    #[Transient]
    protected ?Category $category = null;

..  code-block:: php
    :caption: After

    use TYPO3\CMS\Extbase\Attribute\IgnoreValidation;

    #[IgnoreValidation]
    protected ?Category $category = null;

Extensions that also support TYPO3 v13.4 or v14 keep :php:`#[Transient]` next
to :php:`#[IgnoreValidation]` until they drop support for these versions.
Extbase in TYPO3 v13.4 and v14 does not evaluate unknown property attributes,
so the same code works in TYPO3 v13.4, v14 and v15.

..  index:: PHP-API, NotScanned, ext:extbase
