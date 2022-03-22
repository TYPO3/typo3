.. include:: /Includes.rst.txt

.. _feature-93100-1710488213:

==================================================================
Feature: #93100 - Allow to directly declare static route variables
==================================================================

See :issue:`93100`

Description
===========

Instead of having to use custom route aspect mappers, implementing
:php:`StaticMappableAspectInterface`, to avoid having `&cHash=` signatures
being applied to the generated URL, variables now can be simply declared
`static` in the corresponding route enhancer configuration.

Impact
======

By using the new `static` route configuration directive, custom aspect
mapper implementations can be avoided. However, static route variables
are only applied for a particular variable name if

 * there is not other aspect mapper configured - aspect mappers are
   considered more specific and will take precedence
 * there is a companion `requirements` definition which narrows the
   set of possible values, and should be as restrictive as possible
   to avoid potential cache flooding - `static` routes variables are
   ignored, if there is no corresponding `requirements` definition

Example
-------

..  code-block:: yaml

    routeEnhancers:
      Verification:
        type: Simple
        routePath: '/verify/{code}'
        static:
          code: true
        requirements:
          # only allows SHA1-like hex values - which still allows lots
          # of possible combinations - thus, for this particular example
          # the handling frontend controller should be uncached as well
          #
          # hint: if `static` is set, `requirements` must be set as well
          code: '[a-f0-9]{40}'

As a result, using the URI query parameters `&code=11f6ad8ec52a2984abaafd7c3b516503785c2072`
would generate the URL `https://example.org/verify/11f6ad8ec52a2984abaafd7c3b516503785c2072`.

.. index:: Frontend, YAML, ext:core
