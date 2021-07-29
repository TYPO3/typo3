.. include:: ../../Includes.txt

======================================================
Feature: #82511 - EXT:form add HTML5 date form element
======================================================

See :issue:`82511`

Description
===========


Date form element
-----------------

The form framework contains a new form element called :yaml:`Date` which is technically an HTML5 'date'
form element.

The following snippet shows a comprehensive example on how to use the new element within the form
definition including the new :yaml:`DateRange` validator:

.. code-block:: yaml

    type: Date
    identifier: date-1
    label: Date
    defaultValue: '2018-03-02'
    properties:
      # default if not defined: 'd.m.Y' (https://php.net/manual/de/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters)
      displayFormat: 'd.m.Y'
      fluidAdditionalAttributes:
        min: '2018-03-01'
        max: '2018-03-30'
        step: '1'
    validators:
      -
        identifier: DateRange
        options:
          minimum: '2018-03-01'
          maximum: '2018-03-30'

The properties :yaml:`defaultValue`, :yaml:`properties.fluidAdditionalAttributes.min`,
:yaml:`properties.fluidAdditionalAttributes.max` and the :yaml:`DateRange` validator options :yaml:`minimum` and
:yaml:`maximum` must have the format 'Y-m-d' which represents the RFC 3339 'full-date' format.

Read more: https://www.w3.org/TR/2011/WD-html-markup-20110405/input.date.html

The :yaml:`DateRange` validator is the server side validation equivalent to the client side validation
through the :yaml:`min` and :yaml:`max` HTML attribute and should always be used in combination.
If the :yaml:`DateRange` validator is added to the form element within the form editor, the :html:`min` and
:html:`max` HTML attributes are added automatically.

The property :yaml:`properties.displayFormat` defines the display format of the submitted value within the
summary step, email finishers etc. but **not** for the form element value itself.
The display format of the form element value depends on the browser settings and can not be defined!

Read more: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date#Value

Browsers which do not support the HTML5 date element gracefully degrade to a text input.
The HTML5 date element always normalizes the value to the format Y-m-d (RFC 3339 'full-date').
With a text input, by default the browser has no recognition of which format the date should be in.
A workaround could be to put a pattern attribute on the date input. Even though the date input does
not use it, the text input fallback will. By default, the HTML attribute
'pattern="([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"' is rendered on the date form
element. Note that this basic regular expression does not support leap years and does not check for
the correct number of days in a month. But as a start, this should be sufficient.
The same pattern is used by the form editor to validate the properties :yaml:`defaultValue` and the
:yaml:`DateRange` validator options :yaml:`minimum` and :yaml:`maximum`.

Read more: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date#Handling_browser_support


DateRange server side validation
--------------------------------

A new validator called DateRange is available.
The input must be a DateTime object.
This input can be tested against a minimum date and a maximum date.
The minimum date and the maximum date are strings.
The minimum date and the maximum date can be configured through the validator options.

.. code-block:: yaml

    validators:
      -
        identifier: DateRange
        options:
          # The PHP \DateTime object format of the `minimum` and `maximum` option
          # @see https://php.net/manual/de/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters
          # 'Y-m-d' is the default value of this validator and must have this value
          # if you use this validator in combination with the `Date` form element.
          # This is because the HTML5 date value is always a RFC 3339 'full-date' format (Y-m-d)
          # @see https://www.w3.org/TR/2011/WD-html-markup-20110405/input.date.html#input.date.attrs.value
          format : 'Y-m-d'
          minimum: '2018-03-01'
          maximum: '2018-03-30'


Impact
======

It is now possible to add an HTML5 date form element including corresponding HTML attributes and
validators.

.. index:: Frontend, Backend, ext:form
