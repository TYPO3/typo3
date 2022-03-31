.. include:: /Includes.rst.txt

==============================================================================================
Important: #94615 - Fluid view helpers f:link.external and f:uri.external use https by default
==============================================================================================

See :issue:`94615`

Description
===========

When using the Fluid view helpers :html:`f:uri.external` or :html:`f:link.external` without
an explicitly specified scheme, the target link now uses :html:`https` instead of :html:`http`.

Given the following Fluid snippets:

.. code-block:: html

   <f:link.external uri="www.some-domain.tld">some content</f:link.external>
   <f:uri.external uri="www.some-domain.tld" />

The result before:

.. code-block:: html

   <a href="http://www.some-domain.tld">some content</a>
   http://www.some-domain.tld

New result:

.. code-block:: html

   <a href="https://www.some-domain.tld">some content</a>
   https://www.some-domain.tld

If the new default can not be used, the :html:`http` scheme needs to be specified. Examples:

.. code-block:: html

   <f:link.external uri="http://www.some-domain.tld">some content</f:link.external>
   <f:link.external uri="www.some-domain.tld" defaultScheme="http">some content</f:link.external>
   <f:uri.external uri="http://www.some-domain.tld" />
   <f:uri.external uri="www.some-domain.tld" defaultScheme="http" />


.. index:: Fluid, ext:fluid
