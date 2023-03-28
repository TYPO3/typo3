.. include:: /Includes.rst.txt

.. _feature-84594-1674211080:

======================================================
Feature: #84594 - Additional parameters to email links
======================================================

See :issue:`84594`

Description
===========

Editors in TYPO3 now have more possibilities to set options when
creating a link to a specific email address, in accordance with the "mailto:"
protocol.

This way, editors can now pre-fill the fields "subject", "CC", "BCC"
and "body" in the TYPO3 backend when creating a link to an email
address, which are then percent-encoded to the actual email link.

In addition, the `<f:link.email>` ViewHelper has the same additional
attributes as well:

..  code-block:: html

    <f:link.email
       email="foo@bar.tld"
       subject="Check out this website"
       cc="foo@example.com"
       bcc="bar@example.com"
    >
         some custom content
    </f:link.email>

All of the properties and the link fields are optional.

For custom email links, it is now also possible to restrict the additional
options via TCA:

Example configuration
---------------------

..  code-block:: php

    'header_link' => [
        'label' => 'Link',
        'config' => [
            'type' => 'link',
            'allowedTypes' => ['email'],
            'size' => 50,
            'appearance' => [
                // new options are "body", "cc", "bcc" and "subject"
                'allowedOptions' => ['body', 'cc'],
            ],
        ],
    ],

Impact
======

Editors now have more flexibility when creating links to emails in the
TYPO3 backend.

Integrators have more flexibility when creating links within Fluid
templates.

.. index:: Backend
