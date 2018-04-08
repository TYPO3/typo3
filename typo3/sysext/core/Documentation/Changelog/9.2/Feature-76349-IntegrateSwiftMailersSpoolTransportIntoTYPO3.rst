.. include:: ../../Includes.txt

=====================================================================
Feature: #76349 - Integrate Swift Mailer's spool transport into TYPO3
=====================================================================

See :issue:`76349`

Description
===========

The default behavior of the TYPO3 mailer is to send the email messages immediately. You may, however, want to avoid
the performance hit of the communication to the email server, which could cause the user to wait for the next page to
load while the email is being sent. This can be avoided by choosing to "spool" the emails instead of sending them directly.

This makes the mailer not attempt to send the email message but instead save it somewhere such as a file. Another
process can then read from the spool and take care of sending the emails in the spool. Currently only spooling to file
or memory is supported.

.. note::

   If you are running a multi-head environment consider using a different solution for mail spooling
   than the options presented here.


Spool Using Memory
==================

When you use spooling to store the emails to memory, they will get sent right before the kernel terminates. This means
the email only gets sent if the whole request got executed without any unhandled exception or any errors. To configure
this spool, use the following configuration:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_type'] = 'memory';


Spool Using Files
=================

When using the filesystem for spooling, you need to define in which folder TYPO3 stores the spooled files.
This folder will contain files for each email in the spool. So make sure this directory is writable by TYPO3 and not
accessible to the world (outside of the webroot).

In order to use the spool with files, use the following configuration:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_type'] = 'file';
   $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_filepath'] = '/folder/of/choice';

Now, when TYPO3 is instructed to send an email, it will not actually be sent but instead added to the spool. Sending the
messages from the spool is done separately. There is a console command to send the messages in the spool:

.. code-block:: php

   ./typo3/sysext/core/bin/typo3 swiftmailer:spool:send


It has an option to limit the number of messages to be sent:

.. code-block:: php

   ./typo3/sysext/core/bin/typo3 swiftmailer:spool:send --message-limit=10


You can also set the time limit in seconds:

.. code-block:: php

   ./typo3/sysext/core/bin/typo3 swiftmailer:spool:send --time-limit=10


Of course you will not want to run this manually in reality. Instead, the console command should be triggered by a cron
job or scheduled task and run at a regular interval.

.. index:: PHP-API
