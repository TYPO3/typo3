
.. include:: ../../Includes.txt

==================================================================================================
Deprecation: #66588 - POST Data in selectviewhelper should have higher priority than "value" value
==================================================================================================

See :issue:`66588`

Description
===========

Submitted form data has precedence over value arguments.

This adjusts the behavior of all Form ViewHelpers so that any
submitted value is redisplayed even if a "value" argument has been
specified.

The issue with this, however, was that upon re-display of the form due
to property-mapping or validation errors the value argument had
precedence over the previously submitted value.


Impact
======

This is a breaking change if you expect the previous behavior of form
ViewHelpers always being pre-populated with the specified value
attribute / bound object property even when re-displaying the form upon
validation errors.

Besides this the change marks `AbstractFormFieldViewHelper::getValue()` as
deprecated. If you call that method in your custom ViewHelpers you should use
`AbstractFormFieldViewHelper::getValueAttribute()` instead and call
`AbstractFormFieldViewHelper::addAdditionalIdentityPropertiesIfNeeded()`
explicitly if the ViewHelper might be bound to (sub)entities.

The default usage of getValueAttribute() did not respect the submitted form data,
because not every viewhelper needs this feature. But you can enable the usage of
the form data by setting `AbstractFormFieldViewHelper::respectSubmittedDataValue` to TRUE.


.. index:: Fluid, PHP-API
