..  include:: /Includes.rst.txt

..  _breaking-108093-1763042697:

=================================================================================
Breaking: #108093 - Add respectSubmittedDataValue argument in password ViewHelper
=================================================================================

See :issue:`108093`

Description
===========

The :fluid:`<f:form.password>` ViewHelper now provides the argument
:php:`respectSubmittedDataValue` which allows to configure, if submitted field
value will be put into the HTML response of the form on validation errors after
submission. The default value of the new argument is set to :php:`false`,
resulting in a submitted field value to be cleared on validation errors of the
form.


Impact
======

A submitted password will not remain as value for the password field in case
form validation fails.


Affected installations
======================

TYPO3 instances using the :fluid:`<f:form.password>` ViewHelper.


Migration
=========

In case the submitted field value of the :php:`f:form.password` ViewHelper must
remain on validation errors of the form, users must adapt the password
ViewHelper usage as shown below:

.. code-block:: php

    <f:form.password name="myPassword" respectSubmittedDataValue="1" />

..  index:: Fluid, FullyScanned, ext:fluid
