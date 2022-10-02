.. include:: /Includes.rst.txt

.. _feature-94544:

=====================================================
Feature: #94544 - Add new SMTP configuration settings
=====================================================

See :issue:`94544`

Description
===========

A few more SMTP options are now supported by TYPO3 and can be set in the Install Tool:

`transport_smtp_restart_threshold`
    Sets the maximum number of messages to send before re-starting the transport.

`transport_smtp_restart_threshold_sleep`
    The number of seconds to sleep between stopping and re-starting the transport

`transport_smtp_ping_threshold`
    Sets the minimum number of seconds required between two messages, before the server is pinged. If
    the transport wants to send a message and the time since the last message exceeds the specified
    threshold, the transport will ping the server first (NOOP command) to check if the connection is
    still alive. Otherwise the message will be sent without pinging the server first.

Do not set the threshold too low, as the SMTP server may drop the connection if there are too many
non-mail commands (like pinging the server with NOOP).

It is now also possible to define an array with SMTP stream options in the
:file:`AdditionalConfiguration.php` file.

Configuration Example:

..  code-block:: php

    return [
        //....
        'MAIL' => [
            'transport' => 'smtp',
            'transport_smtp_server' => 'localhost:1025',
            'transport_smtp_stream_options' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ],
        ],
        //....
    ];

Impact
======

Now it is possible to set more options for some SMTP cases.

.. index:: LocalConfiguration, ext:core
