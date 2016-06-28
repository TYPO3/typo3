.. include:: ../../../Includes.txt


.. _reference-rules-date:

====
date
====

Checks if the submitted value is a valid date, and the format is equal to
the one set in TypoScript.

The format configuration is like the PHP strftime() conversion specifiers.
The message shown to the visitor supports the format as well, but will be
shown to the visitor in a human readable way.
%e-%m-%Y becomes d-mm-yyyy in English.


.. _reference-rules-date-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-date-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

:aspect:`Default:`
    *local language:*"The value does not appear to be a valid date"


.. _reference-rules-date-format:

format
======

:aspect:`Property:`
    format

:aspect:`Data type:`
    strftime-conf

:aspect:`Description:`
    The format of the submitted data.

    See the PHP-manual (strftime) for the codes, or datatype "strftime-conf"
    in the TYPO3 document TSref.

:aspect:`Default:`
    %e-%m-%Y


.. _reference-rules-date-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

    The %format marker will be replaced with a human readable format.
    %e-%m-%Y becomes d-mm-yyyy in English.

:aspect:`Default:`
    *local language:*"(%format)"


.. _reference-rules-date-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.

[tsref:(cObject).FORM->rules.date]

