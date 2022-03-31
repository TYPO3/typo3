.. include:: /Includes.rst.txt

====================================================================
Feature: #93835 - AddErrorForProperty function for AbstractValidator
====================================================================

See :issue:`93835`

Description
===========

When validating Extbase models, it could be helpful to assign the encountered
error to a certain property. This is already possible by using
:php:`$this->result->forProperty($propertyPath)->addError($error);`. This
method however is cumbersome and requires knowledge about the result object.
To ease the pain for developers, a convenience method :php:`addErrorForProperty`
is now available.

Use it like this in a validator class:

.. code-block:: php

   public function isValid(): void
   {
      // validation
      $this->addErrorForProperty(
         'object.property.name',
         $this->translateErrorMessage(
             'validator.errormessage',
             'my-ext'
         ),
         // tstamp_of_now_as_errorcode
         123456789
      );
   }


Impact
======

The new method enables developers adding custom error messages to validation
results of properties in a convenient way.

.. index:: ext:extbase
