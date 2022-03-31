.. include:: /Includes.rst.txt

==========================================================
Deprecation: #80420 - EmailFinisher single address options
==========================================================

See :issue:`80420`

Description
===========

The :php:`EmailFinisher` of EXT:form has options to set multiple recipients for :code:`To`, :code:`CC` and :code:`BCC`.
For consistency reasons and to limit the number of choices, resulting in easier configuration, the single value configuration options
have will be removed in favour for their respective multi value variants.

For this reason, the following options have been marked as deprecated and will be removed in TYPO3 11.0:

* :yaml:`recipientAddress`
* :yaml:`recipientName`
* :yaml:`replyToAddress`
* :yaml:`carbonCopyAddress`
* :yaml:`blindCarbonCopyAddress`

If any of these options are used, their values will be automatically migrated to their replacements.

Opening and saving a form with the form editor once also performs this migration and makes it permanent.


Impact
======

Any of these options will no longer work in TYPO3 11.0.


Affected Installations
======================

All installations which use EXT:form and its :php:`EmailFinisher`.


Migration
=========

All single value options must be migrated to their list value successors.


Multiple Recipients
-------------------

Change :yaml:`recipientAddress` and :yaml:`recipientName` to :yaml:`recipients`.

Before:

.. code-block:: yaml

   finishers:
     -
       identifier: EmailToReceiver
       options:
         recipientAddress: to@example.org
         recipientName: 'To Example'

After:

.. code-block:: yaml

   finishers:
     -
       identifier: EmailToReceiver
       options:
         recipients:
           to@example.org: 'To Example'


Multiple Reply-To Recipients
----------------------------

Change :yaml:`replyToAddress` to :yaml:`replyToRecipients`. Additionally this allows for setting the name of a Reply-To recipient.

Before:

.. code-block:: yaml

   finishers:
     -
       identifier: EmailToReceiver
       options:
         replyToAddress: rt@example.org

After:

.. code-block:: yaml

   finishers:
     -
       identifier: EmailToReceiver
       options:
         replyToRecipients:
           rt@example.org@example.org: 'Reply-To Example'


Multiple Carbon Copy (CC) Recipients
------------------------------------

Change :yaml:`carbonCopyAddress` to :yaml:`carbonCopyRecipients`. Additionally this allows for setting the name of a CC recipient.

Before:

.. code-block:: yaml

   finishers:
     -
       identifier: EmailToReceiver
       options:
         carbonCopyAddress: cc@example.org

After:

.. code-block:: yaml

   finishers:
     -
       identifier: EmailToReceiver
       options:
         carbonCopyRecipients:
           cc@example.org: 'CC Example'


Multiple Blind Carbon Copy (BCC) Recipients
-------------------------------------------

Change :yaml:`blindCarbonCopyAddress` to :yaml:`blindCarbonCopyRecipients`. Additionally this allows for setting the name of a BCC recipient.

Before:

.. code-block:: yaml

   finishers:
     -
       identifier: EmailToReceiver
       options:
         blindCarbonCopyAddress: bcc@example.org

After:

.. code-block:: yaml

   finishers:
     -
       identifier: EmailToReceiver
       options:
         blindCarbonCopyRecipients:
           bcc@example.org: 'BCC Example'

.. index:: YAML, NotScanned, ext:form
