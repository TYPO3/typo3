..  include:: /Includes.rst.txt

..  _feature-108227-1763667558:

===========================================================================================================
Feature: #108227 - Allow :php:`#[IgnoreValidation]` and :php:`#[Validate]` attributes for method parameters
===========================================================================================================

See :issue:`108227`

Description
===========

The Extbase attributes :php:`#[IgnoreValidation]` and :php:`#[Validate]` can
now be used for controller action method parameters.

This extends the current validation behavior of these attributes, where either
(1) the complete method or (2) a single parameter is taken into account for
validation. While this works fine for (1), using these attributes in context
of (2) raises some concerns regarding

#.  duplication of parameter names,
#.  validation of the existence of a configured parameter, and
#.  unnecessary complexity regarding reflection-based handling of these
    parameters.

History
-------

When the attributes were originally implemented as Doctrine annotations, the
only possible way to implement behavior (2) was to add the appropriate
annotation to the method-related annotation.

Since :issue:`107229`, annotations are no longer used in Extbase, and PHP
attributes are the only remaining successor in this area. Since PHP attributes
support placement at specific method parameters, the existing attributes can
safely rewritten to be placed (1) at a specific method or (2) at a specific
method parameter.

Usage with method parameters
----------------------------

The capabilities of the existing attributes are expanded to allow placement at
method parameters as well. The previous behavior (defining validation-related
behavior at method level using the existing attribute properties) is now
deprecated and will be removed with TYPO3 v15
(see :ref:`deprecation notice <deprecation-108227-1763668119>`).


Impact
======

By allowing the usage of both :php:`#[IgnoreValidation]` and :php:`#[Validate]`
attributes at method parameter level, the previous error-prone behavior is
now hardened. In addition, this change improves developer experience and pushes
the Extbase ecosystem towards a modernized architecture.


..  index:: Frontend, PHP-API, ext:extbase
