.. include:: ../../../Includes.txt


.. _reference-postprocessors-mail:

====
mail
====

The mail postProcessor sends submitted data by mail.

.. _reference-postProcessor-mail-mail:

Mail
====

Configuration options for the mail to deliver.


.. _reference-postprocessors-mail-ccemail:

ccEmail
-------

:aspect:`Property:`
    ccEmail

:aspect:`Data type:`
    string

:aspect:`Description:`
    Email address the submitted data is sent to as a carbon copy.


.. _reference-postprocessors-mail-organization:

organization
------------

:aspect:`Property:`
    organization

:aspect:`Data type:`
    string

:aspect:`Description:`
    Organization mail header.


.. _reference-postprocessors-mail-priority:

priority
--------

:aspect:`Property:`
    priority

:aspect:`Data type:`
    integer

:aspect:`Description:`
    Priority of the email. Integer value between 1 and 5. If the priority is
    configured, but too high, it will be set to 5, which means very low
    priority.

:aspect:`Default:`
    3


.. _reference-postprocessors-mail-recipientemail:

recipientEmail
--------------

:aspect:`Property:`
    recipientEmail

:aspect:`Data type:`
    string

:aspect:`Description:`
    Email address the submitted data is sent to.


.. _reference-postprocessors-mail-senderemail:

senderEmail
-----------

:aspect:`Property:`
    senderEmail

:aspect:`Data type:`
    string

:aspect:`Description:`
    Email address which is shown as sender of the email (from header).

:aspect:`Default:`
    TYPO3\_CONF\_VARS['MAIL']['defaultMailFromAddress']


.. _reference-postprocessors-mail-senderemailfield:

senderEmailField
----------------

:aspect:`Property:`
    senderEmailField

:aspect:`Data type:`
    string

:aspect:`Description:`
    Name of the form field which holds the sender's email address (from
    header).

    Normally, you can find the (filtered) name in the HTML output between
    the square brackets like tx\_form[name] where name is the name of the
    object.

    Only used if senderEmail is not set.


.. _reference-postprocessors-mail-sendername:

senderName
----------

:aspect:`Property:`
    senderName

:aspect:`Data type:`
    string

:aspect:`Description:`
    Name which is shown as sender of the email (from header).

:aspect:`Default:`
    TYPO3\_CONF\_VARS['MAIL']['defaultMailFromName']


.. _reference-postprocessors-mail-sendernamefield:

senderNameField
---------------

:aspect:`Property:`
    senderNameField

:aspect:`Data type:`
    string

:aspect:`Description:`
    Name of the form field which holds the sender's name (from header).

    Normally you can find the (filtered) name in the HTML output between the
    square brackets like tx\_form[name] where name is the name of the
    object.

    Only used if senderName is not set.


.. _reference-form-subject:

subject
-------

:aspect:`Property:`
    subject

:aspect:`Data type:`
    string

:aspect:`Description:`
    Subject of the email sent by the form.

:aspect:`Default:`
    Formmail on 'Your\_HOST'


.. _reference-postprocessors-mail-subjectfield:

subjectField
------------

:aspect:`Property:`
    subjectField

:aspect:`Data type:`
    string

:aspect:`Description:`
    Name of the form field which holds the subject.

    Normally you can find the (filtered) name in the HTML output between the
    square brackets like tx\_form[name] where name is the name of the
    object.

    Only used if subject is not set.

[tsref:(cObject).FORM->postProcessor.mail]


.. _reference-postprocessors-mail-htmlMailTemplatePath:

htmlMailTemplatePath
--------------------

:aspect:`Property:`
    htmlMailTemplatePath

:aspect:`Data type:`
    string

:aspect:`Description:`
    Name of the template to use for HTML-Content.

    Default is `Html`. Useful to use multiple Mail Postprocessors with different templates.


.. _reference-postprocessors-mail-plaintextMailTemplatePath:

plaintextMailTemplatePath
-------------------------

:aspect:`Property:`
    plaintextMailTemplatePath

:aspect:`Data type:`
    string

:aspect:`Description:`
    Name of the template to use for Plaintext-Content.

    Default is `Plain`. Useful to use multiple Mail Postprocessors with different templates.

.. _reference-postProcessor-mail-messages:

Messages
========

.. _reference-postprocessors-mail-messages-error:

messages.error
--------------

:aspect:`Property:`
    messages.error

:aspect:`Data type:`
    string/ cObject

:aspect:`Description:`
    Overriding the default text of the error message, describing the error.

    When no cObject type is set, the message is a simple string. The value
    can directly be assigned to the messages.error property. If one needs
    the functionality of cObjects, just define the message appropriately.
    Any cObject is allowed.

    For more information about cObjects, take a look in the document TSREF.

    **Example:**

    .. code-block:: typoscript

      messages.error = TEXT
      messages.error {
        data = LLL:EXT:theme/Resources/Private/Language/Form/locallang.xlf:messagesError
      }

    **Example:**

    .. code-block:: typoscript

      messages.error = Error while submitting form

:aspect:`Description:`
    *Local language:*"There was an error when sending the form by mail"


.. _reference-postprocessors-mail-messages-success:

messages.success
----------------

:aspect:`Property:`
    messages.success

:aspect:`Data type:`
    string/ cObject

:aspect:`Description:`
    Overriding the default text of the confirmation message.

    When no cObject type is set, the message is a simple string. The value
    can directly be assigned to the messages.success property. If one needs
    the functionality of cObjects, just define the message appropriately.
    Any cObject is allowed.

    For more information about cObjects, take a look in the document TSREF.

    **Example:**

    .. code-block:: typoscript

      messages.success = TEXT
      messages.success {
        data = LLL:EXT:theme/Resources/Private/Language/Form/locallang.xlf:messagesSuccess
      }

    **Example:**

    .. code-block:: typoscript

      messages.success = Thanks for submitting

:aspect:`Default:`
    *Local language:*"The form has been sent successfully by mail"

