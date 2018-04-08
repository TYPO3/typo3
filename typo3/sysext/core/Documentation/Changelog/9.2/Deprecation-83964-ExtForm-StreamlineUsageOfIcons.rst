.. include:: ../../Includes.txt

==========================================================
Deprecation: #83964 - EXT:form - streamline usage of icons
==========================================================

See :issue:`83964`

Description
===========

With issue #82348 EXT:form icons have been cloned into :file:`EXT:core/Resources/Public/Icons/T3Icons/form`.
Icons are now available with the identifier prefix `form-` (previously `t3-form-icon-`).
For this reason, the old icon identifiers with `t3-form-icon-` prefix have been marked as deprecated and will be
removed in TYPO3v10.


Impact
======

Usage of the following icon identifiers will trigger a deprecation warning:

* `t3-form-icon-advanced-password`
* `t3-form-icon-checkbox`
* `t3-form-icon-content-element`
* `t3-form-icon-date-picker`
* `t3-form-icon-duplicate`
* `t3-form-icon-email`
* `t3-form-icon-fieldset`
* `t3-form-icon-file-upload`
* `t3-form-icon-finisher`
* `t3-form-icon-form-element-selector`
* `t3-form-icon-gridcontainer`
* `t3-form-icon-gridrow`
* `t3-form-icon-hidden`
* `t3-form-icon-image-upload`
* `t3-form-icon-insert-after`
* `t3-form-icon-insert-in`
* `t3-form-icon-multi-checkbox`
* `t3-form-icon-multi-select`
* `t3-form-icon-number`
* `t3-form-icon-page`
* `t3-form-icon-password`
* `t3-form-icon-radio-button`
* `t3-form-icon-single-select`
* `t3-form-icon-static-text`
* `t3-form-icon-summary-page`
* `t3-form-icon-telephone`
* `t3-form-icon-text`
* `t3-form-icon-textarea`
* `t3-form-icon-url`
* `t3-form-icon-validator`

Affected installations
======================

All instances are affected which register one of the icon identifiers listed above through the
:php:`IconRegistry`.


Migration
=========

Use one of the following icon identifier replacements ('deprecated-icon-identifier' => 'new-icon-identifier')

* `t3-form-icon-advanced-password` => `form-advanced-password`
* `t3-form-icon-checkbox` => `form-checkbox`
* `t3-form-icon-content-element` => `form-content-element`
* `t3-form-icon-date-picker` => `form-date-picker`
* `t3-form-icon-duplicate` => `actions-duplicate`
* `t3-form-icon-email` => `form-email`
* `t3-form-icon-fieldset` => `form-fieldset`
* `t3-form-icon-file-upload` => `form-file-upload`
* `t3-form-icon-finisher` => `form-finisher`
* `t3-form-icon-form-element-selector` => `actions-variable-select`
* `t3-form-icon-gridcontainer` => `form-gridcontainer`
* `t3-form-icon-gridrow` => `form-gridrow`
* `t3-form-icon-hidden` => `form-hidden`
* `t3-form-icon-image-upload` => `form-image-upload`
* `t3-form-icon-insert-after` => `form-insert-after`
* `t3-form-icon-insert-in` => `form-insert-in`
* `t3-form-icon-multi-checkbox` => `form-multi-checkbox`
* `t3-form-icon-multi-select` => `form-multi-select`
* `t3-form-icon-number` => `form-number`
* `t3-form-icon-page` => `form-page`
* `t3-form-icon-password` => `form-password`
* `t3-form-icon-radio-button` => `form-radio-button`
* `t3-form-icon-single-select` => `form-single-select`
* `t3-form-icon-static-text` => `form-static-text`
* `t3-form-icon-summary-page` => `form-summary-page`
* `t3-form-icon-telephone` => `form-telephone`
* `t3-form-icon-text` => `form-text`
* `t3-form-icon-textarea` => `form-textarea`
* `t3-form-icon-url` => `form-url`
* `t3-form-icon-validator` => `form-validator`


.. index:: Backend, ext:form, NotScanned
