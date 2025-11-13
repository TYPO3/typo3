..  include:: /Includes.rst.txt

..  _breaking-108097-1763046431:

===============================================
Breaking: #108097 - MailMessage->send() removed
===============================================

See :issue:`108097`

Description
===========

Class :php:`MailMessage` is a data object that should not contain service
methods like :php:`send()`. The following methods have been removed:

* :php:`TYPO3\CMS\Core\Mail\MailMessage->send()`
* :php:`TYPO3\CMS\Core\Mail\MailMessage->isSent()`


Impact
======

Using the above methods on instances of this class will raise fatal PHP errors.


Affected installations
======================

Instances that create :php:`MailMessage` objects and call :php:`send()` or :php:`isSent()`
are affected. The extension scanner is not configured to find affected code since the
method names are too generic.


Migration
=========

The service (usually a controller class) that sends emails should be reconfigured to
get an instance of :php:`TYPO3\CMS\Core\Mail\MailerInterface` injected and should use
that service to :php:`send()` the mail.

Example before:

.. code-block:: php

    use TYPO3\CMS\Core\Mail\MailMessage;

    final readonly class MyController
    {
        public function sendMail()
        {
            $email = new MailMessage();
            $email->subject('Some subject');
            $email->send();
        }
    }

Example after:

.. code-block:: php

    use TYPO3\CMS\Core\Mail\MailMessage;
    use TYPO3\CMS\Core\Mail\MailerInterface;

    final readonly class MyController
    {
        public function __construct(
            private MailerInterface $mailer
        ) {}

        public function sendMail()
        {
            $email = new MailMessage();
            $email->subject('Some subject');
            $this->mailer->send($email);
        }
    }

..  index:: PHP-API, NotScanned, ext:core
