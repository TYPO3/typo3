..  include:: /Includes.rst.txt

..  _breaking-108093-1763042697:

=================================================================================
Breaking: #108093 - Add respectSubmittedDataValue argument in password ViewHelper
=================================================================================

See :issue:`108093`

Description
===========

The :fluid:`<f:form.password>` ViewHelper now provides the argument
:php:`respectSubmittedDataValue`, which allows configuration of whether a
submitted field value will be put into the HTML response of the form on
validation errors after submission. The default value of the new argument is
set to :php:`false`, resulting in a submitted field value being cleared on
validation errors of the form.

Impact
======

A submitted password will not remain as the value for the password field if
form validation fails.

Affected installations
======================

TYPO3 instances using the :fluid:`<f:form.password>` ViewHelper.

Migration
=========

If the submitted field value of the :php:`f:form.password` ViewHelper must
remain on validation errors of the form, users must adapt the password
ViewHelper usage as shown below:

..  code-block:: php

     <f:form.password name="myPassword" respectSubmittedDataValue="1" />

..  index:: Fluid, FullyScanned, ext:fluid
