..  include:: /Includes.rst.txt

..  _deprecation-109152-1741600000:

==============================================
Deprecation: #109152 - Form DatePicker element
==============================================

See :issue:`109152`

Description
===========

The :yaml:`DatePicker` form element type and its associated
:php:`DatePickerViewHelper` and :php:`TimePickerViewHelper` have been
deprecated as part of removing jQuery dependency from
:composer:`typo3/cms-form`. The :yaml:`Date` form element type serves as a
replacement and uses native HTML5 :html:`<input type="date">` without needing
a JavaScript library.

The following components are deprecated:

*   :php:`TYPO3\CMS\Form\Domain\Model\FormElements\DatePicker`
*   :php:`TYPO3\CMS\Form\ViewHelpers\Form\DatePickerViewHelper`
*   :php:`TYPO3\CMS\Form\ViewHelpers\Form\TimePickerViewHelper`
*   The :file:`EXT:form/Resources/Public/JavaScript/frontend/date-picker.js`
    jQuery initialization script

Impact
======

Using the :yaml:`DatePicker` form element type in a form definition will
trigger a PHP :php:`E_USER_DEPRECATED` level error at runtime. The element,
its ViewHelpers, and the JavaScript file will be removed in TYPO3 v15.

Affected installations
======================

All installations that use the :yaml:`DatePicker` form element type in form
definitions created with the TYPO3 Form Framework.

Migration
=========

Replace :yaml:`DatePicker` with the :yaml:`Date` form element type in your
form definitions.

Before:

..  code-block:: yaml

    type: DatePicker
    identifier: date-1
    label: 'Pick a date'
    properties:
      dateFormat: Y-m-d
      enableDatePicker: true

After:

..  code-block:: yaml

    type: Date
    identifier: date-1
    label: 'Pick a date'

The :yaml:`Date` element uses a native HTML5 date input, which does not
require jQuery or additional JavaScript. The
:yaml:`dateFormat` and :yaml:`enableDatePicker` properties are no longer
needed because the browser handles date formatting and the picker natively.

Alternatively, if the native HTML5 date input does not meet your
requirements, you can create a custom form element with a date picker
JavaScript library of your choice.

..  index:: Frontend, YAML, NotScanned, ext:form
