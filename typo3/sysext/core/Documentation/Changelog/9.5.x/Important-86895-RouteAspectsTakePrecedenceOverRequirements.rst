.. include:: /Includes.rst.txt

===================================================================
Important: #86895 - Route aspects take precedence over requirements
===================================================================

See :issue:`86895`

Description
===========

Route `requirements` are ignored for route variables having a corresponding
setting in `aspects`. Imagine there would be an aspect that is mapping internal
value `1` to route value `one` and vice verse - it is not possible to explicitly
define the `requirements` for this case - which is why `aspects` take precedence.

The following example illustrates the mentioned dilemma between route generation
and resolving:

.. code-block:: yaml

   routeEnhancers:
     MyPlugin
       type: 'Plugin'
       namespace: 'my'
       routePath: 'overview/{month}'
       requirements:
         # note: it does not make any sense to declare all values here again
         month: '^(\d+|january|february|march|april|...|december)$'
       aspects:
         month:
           type: 'StaticValueMapper'
           map:
             january: '1'
             february: '2'
             march: '3'
             april: '4'
             may: '5'
             june: '6'
             july: '7'
             august: '8'
             september: '9'
             october: '10'
             november: '11'
             december: '12'

Actually the `map` in the previous example is already defining all valid values.
That's why actually `aspects` take precedence over `requirements` for a specific
`routePath` definition.

.. index:: Frontend, ext:core
