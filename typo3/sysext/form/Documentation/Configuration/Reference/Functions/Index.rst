.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt




.. _form-functions:

Functions
"""""""""


.. _reference-layout:

layout
~~~~~~

Change the default layout for the FORM objects.

The FORM consists of FORM objects, which each have their own layout.
The layout of these objects can be changed for the whole form or just
for a particular object. By default the overall markup is based on
ordered lists with list elements in it, to have a proper layout
framework which is accessible too. Some objects are considered being
container objects, they have child objects. These objects are FORM and
FIELDSET. To have a proper markup for these objects, we use nested
ordered lists.

**Example:**

::

   <form>
           <ol>
                   <li>
                           <fieldset>
                                   <ol>
                                           <li>
                                                   <input />
                                           </li>
                                   </ol>
                           </fieldset>
                   </li>
                   <li>
                           <input />
                   </li>
           </ol>
   </form>

Some people will say that SELECT and OPTGROUP are container objects as
well, and actually they are right: They contain child objects as well.
But they are not allowed to use this markup.


.. _change-layout-for-whole-form:

Change the layout for the whole form
''''''''''''''''''''''''''''''''''''

We can change the layout of the objects, the container wrap and the
element wrap.

::

   lib.form = FORM
   lib.form {
           layout {
                   form (
                           <form>
                                   <containerWrap />
                           </form>
                   )
                   containerWrap (
                           <ol>
                                   <elements />
                           </ol>
                   )
                   elementWrap (
                           <li>
                                   <element />
                           </li>
                   )
                   radio (
                           <label />
                           <input />
                   )
           }
           ...
   }

As you can see, we use a (X)HTML kind of markup, actually it's XML,
with some extra tags. These tags are:

- containerWrap: Marker for container.

- elements: Marker where to put the child elements within a container.

- element: Marker for single element.

- content: Marker which will be filled with cObjects

layout.containerWrap and layout.elementWrap can only be defined for
the whole form, not individually.


.. _reference-layout-containerwrap:

containerWrap
#############

.. container:: table-row

   Property
         containerWrap

   Data type
         string

   Description
         Inner wrap for container objects.

         The <elements /> tag will be substituted with all the child elements,
         including their outer wraps.

   Default
         <ol><elements /></ol>



.. _reference-layout-elementwrap:

elementWrap
###########

.. container:: table-row

   Property
         elementWrap

   Data type
         string

   Description
         Outer wrap for regular objects.

         The <element /> tag will be substituted with the child element.

   Default
         <li><element /></li>



.. _reference-layout-label:

label
#####

.. container:: table-row

   Property
         label

   Data type
         string

   Description
         Layout for the labels.

         The <labelvalue /> tag will be substituted with the label text.

         The <mandatory /> tag will be substituted with the validation rule
         message, styled by it's own layout.

         The <error /> tag will be substituted with the error message from the
         validation rule when the submitted value is not valid.

   Default
         <label><labelvalue /><mandatory /><error /></label>



.. _reference-layout-mandatory:

mandatory
#########

.. container:: table-row

   Property
         mandatory

   Data type
         string

   Description
         Layout for the validation rule message to describe the rule.

         The <mandatoryvalue /> tag will be substituted with the validation
         rule message.

   Default
         <em><mandatoryvalue /></em>



.. _reference-layout-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string

   Description
         Layout for the validation rule error message when the submitted data
         does not validate.

         The <errorvalue /> tag will be substituted with the validation rule
         error message.

   Default
         <strong><errorvalue /></strong>



.. _reference-layout-legend:

legend
######

.. container:: table-row

   Property
         legend

   Data type
         string

   Description
         Layout for the legend.

   Default
         <legend><legendvalue /></legend>



.. _reference-layout-button:

button
######

.. container:: table-row

   Property
         button

   Data type
         string

   Description
         Layout for the BUTTON object.

   Default
         <label /><input />



.. _reference-layout-checkbox:

checkbox
########

.. container:: table-row

   Property
         checkbox

   Data type
         string

   Description
         Layout for the checkbox object.

   Default
         <label /><input />



.. _reference-layout-content:

content
#######

.. container:: table-row

   Property
         content

   Data type
         string

   Description
         Layout for content.

         The <content /> tag is mainly a marker which will be substituted with
         the actual content, probably from cObjects.

   Default
         <content />



.. _reference-layout-fieldset:

fieldset
########

.. container:: table-row

   Property
         fieldset

   Data type
         string

   Description
         Layout for the FIELDSET object.

         The <containerwrap /> tag will be substituted by the outer container
         wrap and includes all child elements.

   Default
         <fieldset><legend /><containerWrap /></fieldset>



.. _reference-layout-fileupload:

fileupload
##########

.. container:: table-row

   Property
         fileupload

   Data type
         string

   Description
         Layout for the FILEUPLOAD object.

   Default
         <label /><input />



.. _reference-layout-form:

form
####

.. container:: table-row

   Property
         form

   Data type
         string

   Description
         Layout for the FORM object.

         The <containerwrap /> tag will be substituted by the outer container
         wrap and includes all child elements.

   Default
         <form><containerWrap /></form>



.. _reference-layout-hidden:

hidden
######

.. container:: table-row

   Property
         hidden

   Data type
         string

   Description
         Layout for the HIDDEN object.

   Default
         <input />



.. _reference-layout-imagebutton:

imagebutton
###########

.. container:: table-row

   Property
         imagebutton

   Data type
         string

   Description
         Layout for the IMAGEBUTTON object.

   Default
         <label /><input />



.. _reference-layout-optgroup:

optgroup
########

.. container:: table-row

   Property
         optgroup

   Data type
         string

   Description
         Layout for the OPTGROUP object.

         The <elements /> tag will be substituted with all the child elements,
         which actually can only be OPTION objects.

   Default
         <optgroup><elements /></optgroup>



.. _reference-layout-option:

option
######

.. container:: table-row

   Property
         option

   Data type
         string

   Description
         Layout for the OPTION object.

   Default
         <option />



.. _reference-layout-password:

password
########

.. container:: table-row

   Property
         password

   Data type
         string

   Description
         Layout for the PASSWORD object.

   Default
         <label /><input />



.. _reference-layout-radio:

radio
#####

.. container:: table-row

   Property
         radio

   Data type
         string

   Description
         Layout for the RADIO object.

   Default
         <label /><input />



.. _reference-layout-reset:

reset
#####

.. container:: table-row

   Property
         reset

   Data type
         string

   Description
         Layout for the RESET object.

   Default
         <label /><input />



.. _reference-layout-select:

select
######

.. container:: table-row

   Property
         select

   Data type
         string

   Description
         Layout for the SELECT object.

         The <elements /> tag will be substituted with all the child elements,
         which only can be OPTGROUP or OPTION objects.

   Default
         <label /><select><elements /></select>



.. _reference-layout-submit:

submit
######

.. container:: table-row

   Property
         submit

   Data type
         string

   Description
         Layout for the SUBMIT object.

   Default
         <label /><input />



.. _reference-layout-textarea:

textarea
########

.. container:: table-row

   Property
         textarea

   Data type
         string

   Description
         Layout for the TEXTAREA object

   Default
         <label /><textarea />



.. _reference-layout-textline:

textline
########

.. container:: table-row

   Property
         textline

   Data type
         string

   Description
         Layout for the TEXTLINE object

   Default
         <label /><input />


[tsref:(cObject).FORM->layout]


.. _change-layout-individual-form:

Change the layout for an individual FORM object
'''''''''''''''''''''''''''''''''''''''''''''''

It's also possible to override the layout setting for a particular
object within the form, like a checkbox. The layout function within an
object only accepts the markup, like this:

::

   lib.form = FORM
   lib.form {
           10 = CHECKBOX
           10 {
                   label = I want to receive the monthly newsletter by email
                   layout {
                           <input />
                           <label />
                   }
           }
           ...
   }

Here we switch the input field and the label, just for this particular
checkbox.


.. _reference-rules:

rules
~~~~~

Add validation rules to the FORM.

Validation rules are a powerfull tool to add validation to the form.
The rules function will always be used at the beginning of the form
and belongs to the FORM object.

This accepts multiple validation rules for one FORM object, but you
have to add these rules one by one. Of course it's also possible to
add validation rules for different FORM objects.

::

   rules {
       1 = required
       1 (
           element = first_name
       )
       2 = required
       2 {
           element = last_name
           showMessage = 0
           error = TEXT
           error {
               value = Please enter your last name
           }
       }
   }

When a rule is added, it will automatically add a message to the
object you've connected the rule with. This message will be shown in
the local language and will tell the user it's input needs to be
according to this rule. The message can be hidden or overruled with
your own text.

The validation will be done by the order of the rules. The validation
can be stopped when a certain rule is not valid. By default all
validation rules will be processed.


.. _reference-rules-alphabetic:

alphabetic
''''''''''

Checks if the submitted value only has the characters a-z or A-Z


.. _reference-rules-alphabetic-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-alphabetic-allowwhitespace:

allowWhiteSpace
###############

.. container:: table-row

   Property
         allowWhiteSpace

   Data type
         boolean

   Description
         If allowWhiteSpace = 1, whitespace is allowed in front of, after or
         between the characters.

   Default
         0



.. _reference-rules-alphabetic-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-alphabetic-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-alphabetic-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         For this rule the default message consists of two parts, the second
         one will only be added when allowWhiteSpace has been set. This is not
         possible when adding your own message.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = Use alphabetic characters
            }

         **Example:**

         ::

            message = Use alphabetic characters

         **Example:**

         ::

            message.value = Use alphabetic characters

   Default
         local language:Use alphabetic characters(, whitespace allowed)



.. _reference-rules-alphabetic-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value contains not only alphabetic characters
            }

         **Example:**

         ::

            error = The value contains not only alphabetic characters

         **Example:**

         ::

            error.value = The value contains not only alphabetic characters

   Default
         local language:The value contains not only alphabetic characters


[tsref:(cObject).FORM->rules.alphabetic]


.. _reference-rules-alphanumeric:

alphanumeric
''''''''''''

Checks if the submitted value only has the characters a-z, A-Z or 0-9


.. _reference-rules-alphanumeric-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-alphanumeric-allowwhitespace:

allowWhiteSpace
###############

.. container:: table-row

   Property
         allowWhiteSpace

   Data type
         boolean

   Description
         If allowWhiteSpace = 1, whitespace is allowed in front of, after or
         between the characters.

   Default
         0



.. _reference-rules-alphanumeric-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-alphanumeric-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-alphanumeric-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         For this rule the default message consists of two parts, the second
         one will only be added when allowWhiteSpace has been set. This is not
         possible when adding your own message.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = Use alphanumeric characters
            }

         **Example:**

         ::

            message = Use alphanumeric characters

         **Example:**

         ::

            message.value = Use alphabetic characters

   Default
         local language:Use alphanumeric characters(, whitespace allowed)



.. _reference-rules-alphanumeric-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value contains not only alphanumeric characters
            }

         **Example:**

         ::

            error = The value contains not only alphanumeric characters

         **Example:**

         ::

            error.value = The value contains not only alphanumeric characters

   Default
         local language:The value contains not only alphanumeric characters


[tsref:(cObject).FORM->rules.alphanumeric]


.. _reference-rules-between:

between
'''''''

Checks if the submitted value is between the given minimum and maximum
value. By default minimum and maximum are excluded, but can be
included in the validation.


.. _reference-rules-between-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-between-minimum:

minimum
#######

.. container:: table-row

   Property
         minimum

   Data type
         integer

   Description
         The minimum value of the comparison



.. _reference-rules-between-maximum:

maximum
#######

.. container:: table-row

   Property
         maximum

   Data type
         integer

   Description
         The maximum value of the comparison



.. _reference-rules-between-inclusive:

inclusive
#########

.. container:: table-row

   Property
         inclusive

   Data type
         boolean

   Description
         If inclusive = 1, the minimum and maximum value are included in the
         comparison.



.. _reference-rules-between-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-between-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-between-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         For this rule the default message consists of two parts, the second
         one will only be added when inclusive has been set. This is not
         possible when adding your own message. The markers %minimum and
         %maximum will be replaced with the values set by TypoScript.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value =  The value must be between %minimum and %maximum
            }

         **Example:**

         ::

            message =  The value must be between %minimum and %maximum

         **Example:**

         ::

            message.value =  The value must be between %minimum and %maximum

   Default
         local language: The value must be between %minimum and %maximum(,
         inclusively)



.. _reference-rules-between-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         For this rule, the error message consists of two parts. The second one
         will be added when inclusive has been set. This is not possible when
         overriding the error message with your own message. The markers
         %minimum and %maximum will be replaced with the values set by
         TypoScript.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value is not between %minimum and %maximum
            }

         **Example:**

         ::

            error = The value is not between %minimum and %maximum

         **Example:**

         ::

            error.value = The value is not between %minimum and %maximum

   Default
         local language: The value is not between %minimum and %maximum(,
         inclusively)


[tsref:(cObject).FORM->rules.between]


.. _reference-rules-date:

date
''''

Checks if the submitted value is a valid date, and the format is equal
to the one set in TypoScript.

The format configuration is like the PHP strftime() conversion
specifiers. The message shown to the visitor supports the format as
well, but will be shown to the visitor in a human readable way.
%e-%m-%Y becomes d-mm-yyyy in English.


.. _reference-rules-date-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-date-format:

format
######

.. container:: table-row

   Property
         format

   Data type
         strftime-conf

   Description
         The format of the submitted data.

         See the PHP-manual (strftime) for the codes, or datatype "strftime-
         conf" in the TYPO3 document TSref.

   Default
         %e-%m-%Y



.. _reference-rules-date-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-date-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-date-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         The %format marker will be replaced with a human readable format.
         %e-%m-%Y becomes d-mm-yyyy in English.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = (%format)
            }

         **Example:**

         ::

            message = (%format)

         **Example:**

         ::

            message.value = (%format)

   Default
         local language: (%format)



.. _reference-rules-date-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value does not appear to be a valid date
            }

         **Example:**

         ::

            error = The value does not appear to be a valid date

         **Example:**

         ::

            error.value = The value does not appear to be a valid date

   Default
         local language: The value does not appear to be a valid date


[tsref:(cObject).FORM->rules.date]


.. _reference-rules-digit:

digit
'''''

Checks if the submitted value only has the characters 0-9.


.. _reference-rules-digit-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-digit-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-digit-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-digit-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = Use digit characters
            }

         **Example:**

         ::

            message = Use digit characters

         **Example:**

         ::

            message.value = Use digit characters

   Default
         local language: Use digit characters



.. _reference-rules-digit-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value contains not only digit characters
            }

         **Example:**

         ::

            error = The value contains not only digit characters

         **Example:**

         ::

            error.value = The value contains not only digit characters

   Default
         local language: The value contains not only digit characters


[tsref:(cObject).FORM->rules.digit]


.. _reference-rules-email:

email
'''''

Checks if the submitted value is a valid email address.

Validates an RFC 2822 email address, except does not allow most
punctuation and non-ascii alphanumeric characters. Also does not take
length requirements into account. Allows domain name and IP addresses,
and ensures that the IP address entered is valid.


.. _reference-rules-email-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-email-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-email-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-email-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = (john.doe@domain.com)
            }

         **Example:**

         ::

            message = (john.doe@domain.com)

         **Example:**

         ::

            message.value = (john.doe@domain.com)

   Default
         local language: (john.doe@domain.com)



.. _reference-rules-email-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = This is not a valid email address
            }

         **Example:**

         ::

            error = This is not a valid email address

         **Example:**

         ::

            error.value = This is not a valid email address

   Default
         local language: This is not a valid email address



[tsref:(cObject).FORM->rules.email]


.. _reference-rules-equals:

equals
''''''

Compares the submitted data of two FORM objects. If they are not
equal, the rule does not validate.

The rule and error messages will be put in the label of the object the
rule is attached with by the property "element".


.. _reference-rules-equals-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.

         The rule and error message will be added to the label of this object



.. _reference-rules-equals-field:

field
#####

.. container:: table-row

   Property
         field

   Data type
         string

   Description
         The name of the object to compare with.

         Like "element" above.



.. _reference-rules-equals-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-equals-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-equals-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         The %field marker will be replaces with the property "field".

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = This field must be equal to '%field'
            }

         **Example:**

         ::

            message = This field must be equal to '%field'

         **Example:**

         ::

            message.value = This field must be equal to '%field'

   Default
         local language: This field must be equal to '%field'



.. _reference-rules-equals-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         The %field marker will be replaces with the property "field".

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value does not equal the value in field '%field'
            }

         **Example:**

         ::

            error = The value does not equal the value in field '%field'

         **Example:**

         ::

            error.value = The value does not equal the value in field '%field'

   Default
         local language: The value does not equal the value in field '%field'


[tsref:(cObject).FORM->rules.equals]


.. _reference-rules-float:

float
'''''

Checks if the submitted value is a floating point number, AKA floats,
doubles or real numbers.

Float depends on your config.locale\_all setting. For German
(config.locale\_all = de\_DE) you get the following values (partly)
with the PHP function localeconv():

'decimal\_point' => string '.' Decimal point character'thousands\_sep'
=> string '' Thousands separator'mon\_decimal\_point' => string ','
Monetary decimal point character'mon\_thousands\_sep' => string '.'
Monetary thousands separator

First both thousands separators are deleted from the float, then the
decimal points are replaced by a dot to get a proper float which PHP
can always handle.


.. _reference-rules-float-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-float-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-float-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-float-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = Enter a float
            }

         **Example:**

         ::

            message = Enter a float

         **Example:**

         ::

            message.value = Enter a float

   Default
         local language: Enter a float



.. _reference-rules-float-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value does not appear to be a float
            }

         **Example:**

         ::

            error = The value does not appear to be a float

         **Example:**

         ::

            error.value = The value does not appear to be a float

   Default
         local language: The value does not appear to be a float


[tsref:(cObject).FORM->rules.float]


.. _reference-rules-greaterthan:

greaterthan
'''''''''''

Checks if the submitted value is greater than the integer set with
TypoScript.


.. _reference-rules-greaterthan-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-greaterthan-minimum:

minimum
#######

.. container:: table-row

   Property
         minimum

   Data type
         integer

   Description
         The submitted value must be greater than the minimum value



.. _reference-rules-greaterthan-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-greaterthan-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-greaterthan-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         The marker %minimum will be replaced with the value set by TypoScript.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = The value must be greater than %minimum
            }

         **Example:**

         ::

            message = The value must be greater than %minimum

         **Example:**

         ::

            message.value = The value must be greater than %minimum

   Default
         local language: The value must be greater than %minimum



.. _reference-rules-greaterthan-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         The marker %minimum will be replaced with the value set by TypoScript.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value does not appear to be greater than %minimum
            }

         **Example:**

         ::

            error = The value does not appear to be greater than %minimum

         **Example:**

         ::

            error.value = The value does not appear to be greater than %minimum

   Default
         local language: The value does not appear to be greater than %minimum


[tsref:(cObject).FORM->rules.greaterthan]


.. _reference-rules-inarray:

inarray
'''''''

Compares the submitted value with the values in the array set in
TypoScript.


.. _reference-rules-inarray-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-inarray-array:

array
#####

.. container:: table-row

   Property
         array

   Data type
         [array of numbers]

   Description
         The array containing the values which will be compared with the
         incoming data

         **Example:**

         ::

            array {
                1 = TYPO3
                2 = FLOW3
                3 = CMS
                4 = OPEN SOURCE
            }



.. _reference-rules-inarray-strict:

strict
######

.. container:: table-row

   Property
         strict

   Data type
         boolean

   Description
         The types of the needle in the haystack are also checked if strict = 1

   Default
         0



.. _reference-rules-inarray-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-inarray-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-inarray-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         By default the value of the The message is a TEXT cObj, but you can
         use other cObj as well. When no cObj type is used it assumes you want
         to use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = Only a few values are possible
            }

         **Example:**

         ::

            message = Only a few values are possible

         **Example:**

         ::

            message.value = Only a few values are possible

   Default
         local language: Only a few values are possible



.. _reference-rules-inarray-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value does not appear to be valid
            }

         **Example:**

         ::

            error = The value does not appear to be valid

         **Example:**

         ::

            error.value = The value does not appear to be valid

   Default
         local language: The value does not appear to be valid


[tsref:(cObject).FORM->rules.inarray]


.. _reference-rules-integer:

integer
'''''''

Checks if the submitted value is an integer.


.. _reference-rules-integer-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-integer-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-integer-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-integer-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         By default the value of the The message is a TEXT cObj, but you can
         use other cObj as well. When no cObj type is used it assumes you want
         to use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = Use a integer
            }

         **Example:**

         ::

            message = Use a integer

         **Example:**

         ::

            message.value = Use a integer

   Default
         local language: Use a integer



.. _reference-rules-integer-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value does not appear to be an integer
            }

         **Example:**

         ::

            error = The value does not appear to be an integer

         **Example:**

         ::

            error.value = The value does not appear to be an integer

   Default
         local language: The value does not appear to be an integer


[tsref:(cObject).FORM->rules.integer]


.. _reference-rules-ip:

ip
''

Checks if the submitted value is an IP address.


.. _reference-rules-ip-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-ip-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-ip-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-ip-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         By default the value of the The message is a TEXT cObj, but you can
         use other cObj as well. When no cObj type is used it assumes you want
         to use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = (123.123.123.123)
            }

         **Example:**

         ::

            message = (123.123.123.123)

         **Example:**

         ::

            message.value = (123.123.123.123)

   Default
         local language: (123.123.123.123)



.. _reference-rules-ip-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value does not appear to be a valid IP address
            }

         **Example:**

         ::

            error = The value does not appear to be a valid IP address

         **Example:**

         ::

            error.value = The value does not appear to be a valid IP address

   Default
         local language: The value does not appear to be a valid IP address


[tsref:(cObject).FORM->rules.ip]


.. _reference-rules-length:

length
''''''

Checks if the submitted value is of a certain length. A minimum length
can be used or a minimum and a maximum length.


.. _reference-rules-length-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-length-minimum:

minimum
#######

.. container:: table-row

   Property
         minimum

   Data type
         integer

   Description
         The minimum length of the submitted value



.. _reference-rules-length-maximum:

maximum
#######

.. container:: table-row

   Property
         maximum

   Data type
         integer

   Description
         The maximum length of the submitted value. Maximum can only be used in
         combination with minimum.



.. _reference-rules-length-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-length-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-length-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         For this rule the default message consists of two parts, the second
         one will only be added when maximum has been set. This is not possible
         when adding your own message. The markers %minimum and %maximum will
         be replaced with the values set by TypoScript.

         By default the value of the The message is a TEXT cObj, but you can
         use other cObj as well. When no cObj type is used it assumes you want
         to use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = The length of the value must have a minimum of %minimum characters, and a maximum of %maximum
            }

         **Example:**

         ::

            message = The length of the value must have a minimum of %minimum characters, and a maximum of %maximum

         **Example:**

         ::

            message.value = The length of the value must have a minimum of %minimum characters, and a maximum of %maximum

   Default
         local language: The length of the value must have a minimum of %minimum
         characters(, and a maximum of %maximum)



.. _reference-rules-length-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         For this rule the default error message consists of two parts, the
         second one will only be added when maximum has been set. This is not
         possible when adding your own error message. The markers %minimum and
         %maximum will be replaced with the values set by TypoScript.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value is less than %minimum characters long, or longer than %maximum
            }

         **Example:**

         ::

            error = The value is less than %minimum characters long, or longer than %maximum

         **Example:**

         ::

            error.value = The value is less than %minimum characters long, or longer than %maximum

   Default
         local language: The value is less than %minimum characters long(, or
         longer than %maximum)


[tsref:(cObject).FORM->rules.length]


.. _reference-rules-lessthan:

lessthan
''''''''

Checks if the submitted value is less than the integer set with
TypoScript.


.. _reference-rules-lessthan-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-lessthan-maximum:

maximum
#######

.. container:: table-row

   Property
         maximum

   Data type
         integer

   Description
         The submitted value must be less than the maximum value



.. _reference-rules-lessthan-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-lessthan-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-lessthan-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         The marker %maximum will be replaced with the value set by TypoScript.

         By default the value of the The message is a TEXT cObj, but you can
         use other cObj as well. When no cObj type is used it assumes you want
         to use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = The value must be less than %maximum
            }

         **Example:**

         ::

            message = The value must be less than %maximum

         **Example:**

         ::

            message.value = The value must be less than %maximum

   Default
         local language: The value must be less than %maximum



.. _reference-rules-lessthan-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         The marker %maximum will be replaced with the value set by TypoScript.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value does not appear to be less than %maximum
            }

         **Example:**

         ::

            error = The value does not appear to be less than %maximum

         **Example:**

         ::

            error.value = The value does not appear to be less than %maximum

   Default
         local language: The value does not appear to be less than %maximum


[tsref:(cObject).FORM->rules.lessthan]


.. _reference-rules-regexp:

regexp
''''''

Checks if the submitted value matches your own regular expression,
using PHP function preg\_match().


.. _reference-rules-regexp-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-regexp-expression:

expression
##########

.. container:: table-row

   Property
         expression

   Data type
         string

   Description
         The submitted value needs to match the expression, given in your
         pattern



.. _reference-rules-regexp-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-regexp-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-regexp-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         By default the value of the The message is a TEXT cObj, but you can
         use other cObj as well. When no cObj type is used it assumes you want
         to use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = Use the right pattern
            }

         **Example:**

         ::

            message = Use the right pattern

         **Example:**

         ::

            message.value = Use the right pattern

   Default
         local language: Use the right pattern



.. _reference-rules-regexp-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value does not match against pattern
            }

         **Example:**

         ::

            error = The value does not match against pattern

         **Example:**

         ::

            error.value = The value does not match against pattern

   Default
         local language: The value does not match against pattern


[tsref:(cObject).FORM->rules.regexp]


.. _reference-rules-required:

required
''''''''

Checks if the submitted value exists and is not empty.

0 or "0" is allowed and the rule will return true


.. _reference-rules-required-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-required-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-required-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-required-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         By default the value of the The message is a TEXT cObj, but you can
         use other cObj as well. When no cObj type is used it assumes you want
         to use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = Required
            }

         **Example:**

         ::

            message = Required

         **Example:**

         ::

            message.value = Required

   Default
         local language: Required



.. _reference-rules-required-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = This field is required
            }

         **Example:**

         ::

            error = This field is required

         **Example:**

         ::

            error.value = This field is required

   Default
         local language: This field is required


[tsref:(cObject).FORM->rules.required]


.. _reference-rules-uri:

uri
'''

This validation rule checks on a URI, which can include all of the
following:

scheme://usern:passw@domain:port/path/file.ext?querystring#fragment


.. _reference-rules-uri-element:

element
#######

.. container:: table-row

   Property
         element

   Data type
         string

   Description
         Name of the object. Normally you can find the (filtered) name in the
         HTML output between the square brackets like tx\_form[name] where name
         is the name of the object.



.. _reference-rules-uri-breakonerror:

breakOnError
############

.. container:: table-row

   Property
         breakOnError

   Data type
         boolean

   Description
         If breakOnError = 1 and the rule does not validate, all remaining
         rules will not be processed.



.. _reference-rules-uri-showmessage:

showMessage
###########

.. container:: table-row

   Property
         showMessage

   Data type
         boolean

   Description
         If showMessage = 0, a message describing the rule will not be added to
         the label of the object.

   Default
         1



.. _reference-rules-uri-message:

message
#######

.. container:: table-row

   Property
         message

   Data type
         string / cObject

         ->message

   Description
         Overriding the default text of the message, describing the rule.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            message = TEXT
            message {
                value = The value must be a Uniform Resource Identifier (URI)
            }

         **Example:**

         ::

            message = The value must be a Uniform Resource Identifier (URI)

         **Example:**

         ::

            message.value = The value must be a Uniform Resource Identifier (URI)

   Default
         local language: The value must be a hostname



.. _reference-rules-uri-error:

error
#####

.. container:: table-row

   Property
         error

   Data type
         string / cObject

         ->error

   Description
         Overriding the default text of the error message, describing the
         error.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            error = TEXT
            error {
                value = The value does not appear to be a Uniform Resource Identifier (URI)
            }

         **Example:**

         ::

            error = The value does not appear to be a Uniform Resource Identifier (URI)

         **Example:**

         ::

            error.value = The value does not appear to be a Uniform Resource Identifier (URI)

   Default
         local language: The value does not appear to be a hostname


[tsref:(cObject).FORM->rules.uri]


.. _reference-filters:

filters
~~~~~~~

Add filters to the FORM objects.

This accepts multiple filters for one FORM object, but you have to add
these filters one by one. The submitted data for this particular
object will be filtered by the assigned filters in the given order.

This filtered data will be shown to the visitor when there are errors
in the form or on a confirmation page. Otherwise the filtered data
will be send by mail to the receiver.

**Example:**

::

   filters {
       1 = alphabetic
       1 {
           allowWhiteSpace = 1
       }
       2 = titlecase
   }

**Submitted data:** john doe3

**Filtered:** John Doe

**Note:**

All submitted data will be filtered by a Cross Site Scripting (XSS)
filter by default to prevent this security issue.


.. _reference-filters-alphabetic:

alphabetic
''''''''''

Removes all characters which are not in the range a-z or A-Z. With the
setting allowWhiteSpace, spaces are allowed as well.


.. _reference-filters-alphabetic-allowwhitespace:

allowWhiteSpace
###############

.. container:: table-row

   Property
         allowWhiteSpace

   Data type
         boolean

   Description
         If allowWhiteSpace = 1, whitespace is allowed in front of, after or
         between the characters.

   Default
         0


[tsref:(cObject).FORM->filters.alphabetic]


.. _reference-filters-alphanumeric:

alphanumeric
''''''''''''

Removes all characters which are not in the range a-z, A-Z or 0-9.
With the setting allowWhiteSpace, spaces are allowed as well.


.. _reference-filters-alphanumeric-allowwhitespace:

allowWhiteSpace
###############

.. container:: table-row

   Property
         allowWhiteSpace

   Data type
         boolean

   Description
         If allowWhiteSpace = 1, whitespace is allowed in front of, after or
         between the characters.

   Default
         0


[tsref:(cObject).FORM->filters.alphanumeric]


.. _reference-filters-currency:

currency
''''''''

Changes a number to a formatted version with two decimals. The
decimals point and thousands separator are configurable.

Example:

::

   filters {
           1 = currency
           1 {
                   decimalPoint = ,
                   thousandSeparator = space
       }
   }

**input:** 100000.99

**filtered:** 100 000,99


.. _reference-filters-currency-decimalpoint:

decimalPoint
############

.. container:: table-row

   Property
         decimalPoint

   Data type
         string

   Description
         Value for the decimal point, mostly a dot '.' or a comma ','

   Default
         .



.. _reference-filters-currency-thousandseparator:

thousandSeparator
#################

.. container:: table-row

   Property
         thousandSeparator

   Data type
         string

   Description
         Value for the thousand separator.

         Special values:

         **space** : Adds a space as thousand separator

         **none** : No thousand separator

   Default
         ,


[tsref:(cObject).FORM->filters.currency]


.. _reference-filters-digit:

digit
'''''

Removes all characters which are not in the range 0-9.


.. _reference-filters-integer:

integer
'''''''

Integers can be specified in decimal (10-based), optionally preceded
by a sign (- or +).


.. _reference-filters-lowercase:

lowercase
'''''''''

Returns the incoming value with all alphabetic characters converted to
lowercase. Alphabetic is determined by the Unicode character
properties.


.. _reference-filters-regexp:

regexp
''''''

Removes matches in the submitted data found by the pattern.


.. _reference-filters-regexp-expression:

expression
##########

.. container:: table-row

   Property
         expression

   Data type
         boolean

   Description
         The pattern holding the characters which need to be deleted


[tsref:(cObject).FORM->filters.regexp]


.. _reference-filters-removexss:

removexss
'''''''''

This filter will process all incoming data by default. There is no
need to add this filter.

It filters the incoming data on possible Cross Site Scripting attacks
and renders the incoming data safe by removing potential XSS code and
adding a replacement string which destroys the tags.


.. _reference-filters-stripnewlines:

stripnewlines
'''''''''''''

Convenient for textareas. It removes whitelines from the submitted
value.


.. _reference-filters-titlecase:

titlecase
'''''''''

Returns the incoming value with all alphabetic characters converted to
title case. Alphabetic is determined by the Unicode character
properties.

**Example:**

**input:** kasper skårhøj

**filtered:** Kasper Skårhøj


.. _reference-filters-trim:

trim
''''

Strips characters from the beginning and the end of the submitted
value according to the list of characters. If no character list is
set, it will only trim an ordinary space, a tab, a new line, a
carriage return, the NUL-byte and a vertical tab.


.. _reference-filters-trim-characterlist:

characterList
#############

.. container:: table-row

   Property
         characterList

   Data type
         string

   Description
         List of characters to be trimmed

         See the PHP-manual (trim) for the options of the charlist.


[tsref:(cObject).FORM->filters.regexp]


.. _reference-filters-uppercase:

uppercase
'''''''''

Returns the incoming value with all alphabetic characters converted to
uppercase. Alphabetic is determined by the Unicode character
properties.


.. _reference-postprocessors:

postprocessors
~~~~~~~~~~~~~~

Add postprocessors to the FORM.

Postprocessors define how TYPO3 processes submitted forms after the
form is rendered according to filters and rules.

This accepts multiple postprocessors for one FORM object, but you have
to add these postprocessors one by one. Currently only one
postprocessor exists (mail).

**Example:**

::

   postProcessor {
           1 = mail
           1 {
                   recipientEmail = bar@foo.org
                   senderEmail = foo@bar.com
           }
   }

The processing will be done in order of the postprocessors.


.. _reference-postprocessors-mail:

mail
''''

The mail postprocessor sends submitted data by mail.


.. _reference-postprocessors-mail-senderemail:

senderEmail
###########

.. container:: table-row

   Property
         senderEmail

   Data type
         string

   Description
         Email address which is shown as sender of the email (from-header).

   Default
         TYPO3\_CONF\_VARS['MAIL']['defaultMailFromAddress']



.. _reference-postprocessors-mail-senderemailfield:

senderEmailField
################

.. container:: table-row

   Property
         senderEmailField

   Data type
         string

   Description
         Name of the form field which holds the sender's email address (from-
         header). Normally you can find the (filtered) name in the HTML output
         between the square brackets like tx\_form[name] where name is the name
         of the object.

         Only used if senderEmail is not set.



.. _reference-postprocessors-mail-sendername:

senderName
##########

.. container:: table-row

   Property
         senderName

   Data type
         string

   Description
         Name which is shown as sender of the email (from-header).

   Default
         TYPO3\_CONF\_VARS['MAIL']['defaultMailFromName']



.. _reference-postprocessors-mail-sendernamefield:

senderNameField
###############

.. container:: table-row

   Property
         senderNameField

   Data type
         string

   Description
         Name of the form field which holds the sender's name (from-header).
         Normally you can find the (filtered) name in the HTML output between
         the square brackets like tx\_form[name] where name is the name of the
         object.

         Only used if senderName is not set.



.. _reference-form-subject:

subject
#######

.. container:: table-row

   Property
         subject

   Data type
         string

   Description
         Subject of the email sent by the form.

   Default
         Formmail on 'Your\_HOST'



.. _reference-postprocessors-mail-subjectfield:

subjectField
############

.. container:: table-row

   Property
         subjectField

   Data type
         string

   Description
         Name of the form field which holds the subject.

         Normally you can find the (filtered) name in the HTML output between
         the square brackets like tx\_form[name] where name is the name of the
         object.

         Only used if subject is not set.



.. _reference-postprocessors-mail-recipientemail:

recipientEmail
##############

.. container:: table-row

   Property
         recipientEmail

   Data type
         string

   Description
         Email address the submitted data gets sent to.



.. _reference-postprocessors-mail-ccemail:

ccEmail
#######

.. container:: table-row

   Property
         ccEmail

   Data type
         string

   Description
         Email address the submitted data gets sent to as a carbon copy.



.. _reference-postprocessors-mail-priority:

priority
########

.. container:: table-row

   Property
         priority

   Data type
         integer

   Description
         Priority of the mail message. Integer value between 1 and 5. If the
         priority is configured, but too big, it will be set to 5, which means
         very low.

   Default
         3



.. _reference-postprocessors-mail-organization:

organization
############

.. container:: table-row

   Property
         organization

   Data type
         string

   Description
         Organization mail header.



.. _reference-postprocessors-mail-messages-success:

messages.success
################

.. container:: table-row

   Property
         messages.success

   Data type
         string / cObject ->success

   Description
         Overriding the default text of the confirmation message.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            messages.success = TEXT
            messages.success {
                value = Thanks for submitting
            }

         **Example:**

         ::

            messages.success = Thanks for submitting

         **Example:**

         ::

            messages.success.value = Thanks for submitting

   Default
         Local language:

         The form has been sent successfully by mail



.. _reference-postprocessors-mail-messages-error:

messages.error
##############

.. container:: table-row

   Property
         messages.error

   Data type
         String / cObject ->error

   Description
         Overriding the default text of the error message.

         By default the value of the message is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the
         message property or indirectly to the value property of the message.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            messages.error = TEXT
            messages.error {
                value = Error while submitting form
            }

         **Example:**

         ::

            messages.error = Error while submitting form

         **Example:**

         ::

            messages.error.value = Error while submitting form

   Default
         Local language:

         There was an error when sending the form by mail


[tsref:(cObject).FORM->postProcessor.mail]
