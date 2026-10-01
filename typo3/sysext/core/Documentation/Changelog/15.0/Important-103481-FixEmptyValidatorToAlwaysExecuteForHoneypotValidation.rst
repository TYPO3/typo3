..  include:: /Includes.rst.txt

..  _important-103481-1741603200:

==========================================================================
Important: #103481 - Always execute EmptyValidator for honeypot validation
==========================================================================

See :issue:`103481`

Description
===========

The :php-short:`\TYPO3\CMS\Form\Mvc\Validation\EmptyValidator` used by the form framework's honeypot mechanism is
intended to be executed for every value. However, the validator inherited
:php:`$acceptsEmptyValues = true` from :php-short:`\TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator`. As a result,
:php-short:`\TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator::validate()` skipped :php:`isValid()` for empty values.

The property is now set to :php:`false`, so :php:`isValid()` is always called.
This does not change the validation result for empty values, as
:php-short:`\TYPO3\CMS\Form\Mvc\Validation\EmptyValidator::isValid()` does not add an error for them. It aligns the
implementation with the intended validator semantics and ensures that future
logic in :php:`isValid()` also applies to empty values.

..  index:: Frontend, ext:form
