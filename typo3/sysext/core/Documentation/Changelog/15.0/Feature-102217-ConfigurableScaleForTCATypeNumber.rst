..  include:: /Includes.rst.txt

..  _feature-102217-1787855070:

===========================================================
Feature: #102217 - Configurable scale for TCA type "number"
===========================================================

See :issue:`102217`

Description
===========

Fields using TCA type `number` with `format` set to `decimal`
have always been limited to two decimal digits.
The only alternative was the usage of TCA type `number` with
`format` set to `integer` without any decimal digits.

The new TCA column configuration option `scale` makes this number
configurable:

..  code-block:: php

    'conversion_factor' => [
        'label' => 'Conversion factor',
        'config' => [
            'type' => 'number',
            'scale' => 6,
        ],
    ],

The option defaults to :php:`0`, which is the previous behavior.
Values are limited to a scale between :php:`0` and :php:`30`,
the maximum supported by all database platforms.

If the database column is auto-created by TYPO3, the scale is part of the
column definition. For a scale equal to :php:`0`, an integer column will be
generated.

For a scale greater than :php:`0`, the following configuration applies:
The number of digits in front of the decimal point stays
at eight, the configured scale is added on top: The example above results in
:sql:`decimal(14,6)`.

Impact
======

Decimal numbers can be stored and edited with more (or less) than two decimal
digits. The scale is respected by the automatically created database column,
by the DataHandler when persisting a value, and by the FormEngine when
rendering and validating the field in the backend.

Decimal values are rounded on their digits and no longer through a float, so
values with a scale a float can not represent are kept exactly. The new API
method :php-short:`\TYPO3\CMS\Core\Utility\MathUtility::roundDecimalString()` does this rounding and is
available for extensions as well. It accepts digits with an optional sign,
decimal point and exponent, and throws an :php:`\InvalidArgumentException` for
anything else: A value like :php:`'1,5'` is rejected instead of being silently
read as :php:`15`.

Lowering the scale of an existing field leaves stored values with more decimal
digits than the field now allows. Such a value is rounded to the scale when the
record is opened in the backend, and it is stored rounded with the next save of
that record. This matters because a value that is no multiple of the
:html:`step` attribute makes the browser refuse to submit the whole form, and it
does so without any visible message if the field sits on an inactive tab. MySQL
and PostgreSQL round the stored values themselves as soon as the column is
altered, while SQLite keeps decimal values as strings and therefore does not.

The rendered input field carries the scale as its :html:`step` attribute, so a
scale of :php:`6` results in :html:`step="0.000001"`. Browsers evaluate that
attribute in double precision and can no longer do so below :html:`1e-18`, so a
scale above :php:`18` is rendered as :html:`step="any"` instead. Such a field is
still rounded to its scale by the DataHandler and by the FormEngine, but the
browser no longer restricts the value, and the arrows of the input field step
by one.

As part of this, the internal web component
:js:`typo3-formengine-valueslider` now reads the attribute
:html:`scale` instead of :html:`precision`.

Migration
=========

The TCA migration from `format` to `scale` is done like the following:

..  code-block:: php

    // Before
    '<mycolumn_integer>' => [
        'label' => 'Format integer',
        'config' => [
            'type' => 'number',
            'format' => 'integer',
        ],
    ],
    '<mycolumn_decimal>' => [
        'label' => 'Format decimal',
        'config' => [
            'type' => 'number',
            'format' => 'decimal',
        ],
    ],
    // After
    '<mycolumn_integer>' => [
        'label' => 'Format integer',
        'config' => [
            'type' => 'number',
            'scale' => 0,
        ],
    ],
    '<mycolumn_decimal>' => [
        'label' => 'Format decimal',
        'config' => [
            'type' => 'number',
            'scale' => 2,
        ],
    ],

An automatic TCA migration is performed on the fly, migrating all occurrences
of `format` to `scale`.

..  index:: Backend, TCA, ext:core
