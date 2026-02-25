..  include:: /Includes.rst.txt

..  _feature-106681-1740000000:

=======================================================================
Feature: #106681 - Support relative date formats in DateRange validator
=======================================================================

See :issue:`106681`

Description
===========

The :yaml:`DateRange` validator of the form extension now supports relative date
expressions in addition to absolute dates in `Y-m-d` format.

This allows form integrators to define dynamic date constraints that are evaluated
at runtime, such as ensuring a date of birth is at least 18 years in the past or
that a selected date cannot be in the future.

The following relative expressions are supported (matching PHP's
:php:`strtotime()` syntax):

- Named dates: :yaml:`today`, :yaml:`now`, :yaml:`yesterday`, :yaml:`tomorrow`
- Relative offsets: :yaml:`-18 years`, :yaml:`+1 month`, :yaml:`-2 weeks`, :yaml:`+30 days`

These expressions can be used in the :yaml:`options.minimum` and
:yaml:`options.maximum` properties of the :yaml:`DateRange` validator.

Example
=======

Ensure a date of birth is at least 18 years in the past:

..  code-block:: yaml

    type: Date
    identifier: date-of-birth
    label: 'Date of birth'
    validators:
      -
        identifier: DateRange
        options:
          maximum: '-18 years'

Ensure a date is in the future:

..  code-block:: yaml

    type: Date
    identifier: event-date
    label: 'Event date'
    validators:
      -
        identifier: DateRange
        options:
          minimum: '+1 day'

Mixed absolute and relative dates are also supported:

..  code-block:: yaml

    validators:
      -
        identifier: DateRange
        options:
          minimum: '2020-01-01'
          maximum: 'today'

The form editor in the TYPO3 backend has been updated to accept these relative
expressions in the date range fields. The HTML :html:`min` and :html:`max`
attributes on the rendered :html:`<input type="date">` element are automatically
resolved to absolute `Y-m-d` dates at rendering time.


Impact
======

Form integrators can now use relative date expressions in the :yaml:`DateRange`
validator configuration. Existing form definitions using absolute dates continue
to work without changes.

..  index:: Frontend, Backend, ext:form

