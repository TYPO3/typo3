
.. include:: ../../Includes.txt

===============================================
Feature: #62147 - New eval option in TCA: email
===============================================

See :issue:`62147`

Description
===========

A new option has been added to the eval field: email. This will
check if the entered value is a valid e-mail address server-side.
If not, a flash error message will be shown.

Usage:

.. code-block:: php

    'email' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:wd_products/Resources/Private/Language/locallang_db.xlf:tx_wdproducts_domain_model_contactperson.email',
        'config' => array(
            'type' => 'input',
            'size' => 30,
            'eval' => 'email,trim'
        ),
    )


Impact
======

Users don't have to write their own validation classes for e-mail validation.


.. index:: TCA, Backend
