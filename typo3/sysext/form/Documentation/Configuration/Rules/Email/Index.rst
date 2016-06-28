.. include:: ../../../Includes.txt


.. _reference-rules-email:

=====
email
=====

Checks if the submitted value is a valid email address.

Validates an RFC 2822 email address, except does not allow most punctuation
and non-ascii alphanumeric characters. Also does not take length
requirements into account. Allows domain name and IP addresses, and ensures
that the IP address entered is valid.


.. _reference-rules-email-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-email-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

:aspect:`Default:`
    *local language:*"This is not a valid email address"


.. _reference-rules-email-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

:aspect:`Default:`
    *local language:*"(john.doe@domain.com)"


.. _reference-rules-email-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.

[tsref:(cObject).FORM->rules.email]

