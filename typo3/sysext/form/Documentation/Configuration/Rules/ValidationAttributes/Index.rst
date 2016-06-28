.. include:: ../../../Includes.txt


.. _reference-validation-attributes:

=====================
Validation Attributes
=====================

.. contents::
    :local:
    :depth: 1


.. _reference-validation-attributes-allowwhitespace:

allowWhiteSpace
===============

:aspect:`Property:`
    allowWhiteSpace

:aspect:`Data type:`
    boolean

:aspect:`Description:`
    If allowWhiteSpace = 1, whitespace is allowed in front of, after or
    between the characters.

:aspect:`Default:`
    0


.. _reference-validation-attributes-element:

element
=======

:aspect:`Property:`
    element

:aspect:`Data type:`
    string

:aspect:`Description:`
    Name of the object. Normally the "filtered" name can be found in the
    HTML output between the square brackets like tx\_form[name] where "name"
    is the name of the object.


.. _reference-validation-attributes-error:

error
=====

:aspect:`Property:`
    error

:aspect:`Data type:`
    string/ cObject

:aspect:`Description:`
    Overriding the default text of the error message, describing the error.

    When no cObject type is set, the message is a simple string. The value
    can directly be assigned to the message property. If one needs the
    functionality of cObjects, just define the message appropriately. Any
    cObject is allowed.

    For more information about cObjects, take a look in the document TSREF.

    **Example:**

    .. code-block:: typoscript

      error = TEXT
      error {
        data = LLL:EXT:theme/Resources/Private/Language/Form/locallang.xlf:alphabeticError
      }

    **Example:**

    .. code-block:: typoscript

      error = The value contains not only alphabetic characters

:aspect:`Default:`
    Depends on the rule. Check over there.


.. _reference-validation-attributes-message:

message
=======

:aspect:`Property:`
    message

:aspect:`Data type:`
    string/ cObject

:aspect:`Description:`
    Overriding the default text of the message, describing the rule.

    When no cObject type is set, the message is a simple string. The value
    can directly be assigned to the message property. If one needs the
    functionality of cObjects, just define the message appropriately. Any
    cObject is allowed.

    For more information about cObjects, take a look in the document TSREF.

    **Example:**

    .. code-block:: typoscript

      message = TEXT
      message {
        data = LLL:EXT:theme/Resources/Private/Language/Form/locallang.xlf:betweenMessage
      }

    **Example:**

    .. code-block:: typoscript

      message =  The value must be between %minimum and %maximum

:aspect:`Default:`
    Depends on the rule. Check over there.


.. _reference-validation-attributes-showmessage:

showMessage
===========

:aspect:`Property:`
    showMessage

:aspect:`Data type:`
    boolean

:aspect:`Description:`
    If showMessage = 0, a message describing the rule will not be added to
    the label of the object.

:aspect:`Default:`
    1

