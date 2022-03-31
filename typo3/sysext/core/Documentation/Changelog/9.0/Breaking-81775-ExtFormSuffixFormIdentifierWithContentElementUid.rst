.. include:: /Includes.rst.txt

======================================================================
Breaking: #81775 - suffix form identifier with the content element uid
======================================================================

See :issue:`81775`

Description
===========

If a form is rendered through the "form" content element, the identifier
of the form is modified with a suffix.
The form identifier will be suffixed with "-$contentElementUid" (e.g. "myForm-65").


Impact
======

All form element names within the frontend will change from e.g.

.. code-block:: html

    <textarea name="tx_form_formframework[myForm][message]"></textarea>

to

.. code-block:: html

    <textarea name="tx_form_formframework[myForm-65][message]"></textarea>

if the form is rendered through the "form" content element.


Affected Installations
======================

All instances, that render forms through the "form" content element.

.. index:: Frontend, ext:form, NotScanned
