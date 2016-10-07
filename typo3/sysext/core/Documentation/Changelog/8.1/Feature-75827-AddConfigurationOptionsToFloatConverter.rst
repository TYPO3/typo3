
.. include:: ../../Includes.txt

=======================================================================================================
Feature: #75827 - Add configuration options to \TYPO3\CMS\Extbase\Property\TypeConverter\FloatConverter
=======================================================================================================

See :issue:`75827`

Description
===========

It is now possible to define the thousands separator and decimal point for `FloatConverter`.
This can be used to ensure proper sanitation before converting a string to a float.

You can define the configuration for every property like this:

.. code-block:: php

   $this->arguments['<argumentName>']
      ->getPropertyMappingConfiguration()
      ->forProperty('<propertyName>') // this line can be skipped in order to specify the format for all properties
      ->setTypeConverterOption(
         \TYPO3\CMS\Extbase\Property\TypeConverter\FloatConverter::class,
         \TYPO3\CMS\Extbase\Property\TypeConverter\FloatConverter::CONFIGURATION_THOUSANDS_SEPARATOR,
         '.'
      )
      ->setTypeConverterOption(
         \TYPO3\CMS\Extbase\Property\TypeConverter\FloatConverter::class,
         \TYPO3\CMS\Extbase\Property\TypeConverter\FloatConverter::CONFIGURATION_DECIMAL_POINT,
         ','
      );

.. index:: PHP-API, ext:extbase
