.. include:: /Includes.rst.txt

==================================================================
Important: #93635 - Add mail configuration for setting smtp domain
==================================================================

See :issue:`93635`

Description
===========

Some smtp-relay-server require to set the domain under which the sender is
sending a email. As default the EsmtpTransport from Symfony will use the current
domain/IP of the host or container. This will be sufficient for the most of the
servers but some servers requires a valid domain is passed. If this isn't done,
sending emails via such servers will fail.

Setting a valid smtp domain can be achieved by setting
:php:`['MAIL']['transport_smtp_domain']` in the LocalConfiguration.php.
This will set the given domain to the EsmtpTransport agent an send the
correct EHLO-command to the relay-server.

Configuration Example for GSuite.

.. code-block:: php

    return [
        //....
        'MAIL' => [
            'defaultMailFromAddress' => 'webserver@example.com',
            'defaultMailFromName' => 'SYSTEMMAIL',
            'transport' => 'smtp',
            'transport_sendmail_command' => ' -t -i ',
            'transport_smtp_domain' => 'example.com',
            'transport_smtp_encrypt' => '',
            'transport_smtp_password' => '',
            'transport_smtp_server' => 'smtp-relay.gmail.com:587',
            'transport_smtp_username' => '',
        ],
        //....
    ];

Impact
======

Now it is possible to set the smtp mail domain which is required for
some relay-server.

.. index:: LocalConfiguration, ext:core
