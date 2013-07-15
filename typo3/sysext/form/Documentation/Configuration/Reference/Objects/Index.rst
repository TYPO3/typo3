.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _form-objects:

FORM Objects
""""""""""""

You are not bound to the following FORM objects. Whenever FORM will be
put in TypoScript, the contents of this property will be sent to the
FORM plugin, however you can use regular TYPO3 content objects
(cObjects) as well, just like you are used to do. This means you have
the possibility to add COA, TEXT or even HMENU in the FORM TypoScript.


.. _reference-form:

FORM
~~~~

A form will always start with the FORM object. TYPO3 recognizes this
object and sends all TypoScript data to the FORM extension.


.. _reference-form-prefix:

prefix
''''''

.. container:: table-row

   Property
         prefix

   Data type
         string

   Description
         The prefix of the values in the name attributes of the FORM objects.

         <input name=" **prefix** [first\_name]" value="" />

   Default
         tx\_form



.. _reference-form-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         [array of FORM objects]

         ->layout

   Description
         Change the default layout for the FORM objects.

         Addittionaly you can change the layout for the object wrap (<li>
         </li>) or object container wrap (<ul> </ul>).

         **Example:**

         ::

            layout {
                textline (
                    <label />
                    <input />
                )
                elementWrap (
                    <dt>
                        <element />
                    </dt>
                )
                containerWrap (
                    <dl>
                        <elements />
                    </dl>
                )
            }

         The settings above change the layout of the TEXTLINE object, the wrap
         for regular FORM objects and container objects (FORM, FIELDSET)

   Default



.. _reference-form-rules:

rules
'''''

.. container:: table-row

   Property
         rules

   Data type
         [array of numbers]

         ->rules

   Description
         Add validation rules to the FORM.

         This accepts multiple validation rules for one FORM object, but you
         have to add these rules one by one. Of course it's also possible to
         add validation rules for different FORM objects.

         **Example:**

         ::

            rules {
                1 = required
                1 {
                    element = first_name
                }
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

         Validation rules are a powerfull tool to add validation to the form.
         Please take a look at the rules section in this manual.

   Default



.. _reference-form-postprocessor:

postProcessor
'''''''''''''

.. container:: table-row

   Property
         postProcessor

   Data type
         [array of numbers] -> postProcessors

   Description
         Add postprocessors to the FORM

         This accepts multiple postprocessors for one FORM object, but you have
         to add these one by one.

         **Example** :

         ::

            postProcessor {
                    1 = mail
                    1 {
                            recipientEmail = bar@foo.org
                            senderEmail = foo@bar.com
                    }
            }

   Default



.. _reference-form-accept:

accept
''''''

.. container:: table-row

   Property
         accept

   Data type
         string

   Description
         This attribute specifies a comma-separated list of content types that
         a server processing this form will handle correctly.

         User agents may use this information to filter out non-conforming
         files when prompting a user to select files to be sent to the server
         (cf. the INPUT element when type="file").

         RFC2045: For a complete list, see http://www.iana.org/assignments
         /media-types/

   Default



.. _reference-form-accept-charset:

accept-charset
''''''''''''''

.. container:: table-row

   Property
         accept-charset

   Data type
         string

   Description
         This attribute specifies the list of character encodings for input
         data that is accepted by the server processing this form.

         The value is a space- and/or comma-delimited list of charset values.

         The client must interpret this list as an exclusive-or list, i.e., the
         server is able to accept any single character encoding per entity
         received.

         The default value for this attribute is the reserved string "UNKNOWN".
         User agents may interpret this value as the character encoding that
         was used to transmit the document containing this FORM element.

         RFC2045: For a complete list, see http://www.iana.org/assignments
         /character-sets/

   Default



.. _reference-form-action:

action
''''''

.. container:: table-row

   Property
         action

   Data type
         string

   Description
         This attribute specifies a form processing agent.

         User agent behavior for a value other than an HTTP URI is undefined.

         In normal circumstances this will be filled automatically, because the
         form needs to call the same URI where the form resides

   Default



.. _reference-form-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type


   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.

   Default



.. _reference-form-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.

   Default



.. _reference-form-enctype:

enctype
'''''''

.. container:: table-row

   Property
         enctype

   Data type


   Description
         This attribute specifies the content type used to submit the form to
         the server (when the value of method is "post"). The default value for
         this attribute is "application/x-www-form-urlencoded".

         The value "multipart/form-data" should be used in combination with the
         INPUT element, type="file".

   Default
         application/x-www-form-urlencoded



.. _reference-form-id:

id
''

.. container:: table-row

   Property
         id

   Data type


   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         When the attribute "name" is used, this must be the same as "id". This
         will be done automatically. When this is the case, "id" will be set to
         the value of "name".

   Default



.. _reference-form-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type


   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag

   Default



.. _reference-form-method:

method
''''''

.. container:: table-row

   Property
         method

   Data type
         post/get

   Description
         Specifies which HTTP method will be used to submit form data.

         Only form data submitted with the entered or default method will be
         processed.

   Default
         get



.. _reference-form-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that it may be referred to from
         style sheets or scripts.

         W3C Note: This attribute has been included for backwards
         compatibility. Applications should use the id attribute to identify
         elements.

   Default



.. _reference-form-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.

   Default



.. _reference-form-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.


[tsref:(cObject).FORM]


.. _reference-form-example:

Example
'''''''

This example shows a simple payment form. At the beginning you can see
that the layout of the radiobuttons is changed. The label and the
input field are switched:

::

   lib.form = FORM
   lib.form {
       method = post
       layout {
               radio (
                       <input />
                       <label />
               )
       }
       10 = FIELDSET
       10 {
               legend = Name
                   10 = SELECT
                   10 {
                           label = Title
                           10 = OPTION
                           10 {
                                   data = Mr.
                                   selected = 1
                           }
                           20 = OPTION
                           20 {
                                   data = Mrs.
                           }
                           30 = OPTION
                           30 {
                                   data = Ms.
                           }
                           40 = OPTION
                           40 {
                                   data = Dr.
                           }
                           50 = OPTION
                           50 {
                                   data = Viscount
                           }
                   }
                   20 = TEXTLINE
                   20 {
                           label = First name
                   }
                   30 = TEXTLINE
                   30 {
                           label = Last name
                   }
           }
           20 = FIELDSET
           20 {
                   legend = Address
                   10 = TEXTLINE
                   10 {
                           label = Street
                   }
                   20 = TEXTLINE
                   20 {
                           label = City
                   }
                   30 = TEXTLINE
                   30 {
                           label = State
                   }
                   40 = TEXTLINE
                   40 {
                           label = ZIP code
                   }
           }
           30 = FIELDSET
           30 {
                   legend = Payment details
                   10 = FIELDSET
                   10 {
                           legend = Credit card
                           10 = RADIO
                           10 {
                                   label = American Express
                                   name = creditcard
                           }
                           20 = RADIO
                           20 {
                                   label = Mastercard
                                   name = creditcard
                           }
                           30 = RADIO
                           30 {
                                   label = Visa
                                   name = creditcard
                           }
                           40 = RADIO
                           40 {
                                   label = Blockbuster Card
                                   name = creditcard
                           }
                   }
                   20 = TEXTLINE
                   20 {
                           label = Card number
                   }
                   30 = TEXTLINE
                   30 {
                           label = Expiry date
                   }
           }
           40 = SUBMIT
           40 {
                   value = Submit my details
           }
   }


.. _reference-button:

BUTTON
~~~~~~

Creates a push button. User agents should use the value of the value
attribute as the button's label.

Push buttons have no default behavior. Each push button may have
client-side scripts associated with the element's event attributes.
When an event occurs (e.g., the user presses the button, releases it,
etc.), the associated script is triggered.


.. _reference-button-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string / cObject

         ->label

   Description
         The value of the label of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            label = TEXT
            label {
                value = First name
            }

         **Example:**

         ::

            label = First name

         **Example:**

         ::

            label.value = First name



.. _reference-button-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <label />
                <input />
            )

   Default
         <label />

         <input />



.. _reference-button-acceskey:

acceskey
''''''''

.. container:: table-row

   Property
         acceskey

   Data type
         string

   Description
         This attribute assigns an access key to an element.

         An access key is a single character from the document character set.

         Note. Authors should consider the input method of the expected reader
         when specifying an accesskey.

         Pressing an access key assigned to an element gives focus to the
         element.

         The action that occurs when an element receives focus depends on the
         element. For example, when a user activates a link defined by the A
         element, the user agent generally follows the link. When a user
         activates a radio button, the user agent changes the value of the
         radio button. When the user activates a text field, it allows input,
         etc.



.. _reference-button-alt:

alt
'''

.. container:: table-row

   Property
         alt

   Data type
         string

   Description
         For user agents that cannot display images, forms, or applets, this
         attribute specifies alternate text. The language of the alternate text
         is specified by the lang attribute.



.. _reference-button-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-button-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.



.. _reference-button-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-button-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="click">Push this button</label>
            <input type="button" id="click" value="Click me" />



.. _reference-button-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-button-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="button" name="tx_form[21]" value="click" />



.. _reference-button-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-button-tabindex:

tabindex
''''''''

.. container:: table-row

   Property
         tabindex

   Data type
         integer

   Description
         This attribute specifies the position of the current element in the
         tabbing order for the current document. This value must be a number
         between 0 and 32767. User agents should ignore leading zeros.

         The tabbing order defines the order in which elements will receive
         focus when navigated by the user via the keyboard. The tabbing order
         may include elements nested within other elements.

         Elements that may receive focus should be navigated by user agents
         according to the following rules:

         #. Those elements that support the tabindex attribute and assign a
            positive value to it are navigated first. Navigation proceeds from the
            element with the lowest tabindex value to the element with the highest
            value. Values need not be sequential nor must they begin with any
            particular value. Elements that have identical tabindex values should
            be navigated in the order they appear in the character stream.

         #. Those elements that do not support the tabindex attribute or support
            it and assign it a value of "0" are navigated next. These elements are
            navigated in the order they appear in the character stream.

         #. Elements that are disabled do not participate in the tabbing order.

         The actual key sequence that causes tabbing navigation or element
         activation depends on the configuration of the user agent (e.g., the
         "tab" key is used for navigation and the "enter" key is used to
         activate a selected element)

         User agents may also define key sequences to navigate the tabbing
         order in reverse. When the end (or beginning) of the tabbing order is
         reached, user agents may circle back to the beginning (or end).



.. _reference-button-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.



.. _reference-button-type:

type
''''

.. container:: table-row

   Property
         type

   Data type
         string

   Description
         Defines the type of form input control to create.

   Default
         button



.. _reference-button-value:

value
'''''

.. container:: table-row

   Property
         value

   Data type
         string

   Description
         This attribute assigns the initial value to the object.


[tsref:(cObject).FORM.FormObject.BUTTON]


.. _reference-checkbox:

CHECKBOX
~~~~~~~~

Creates a checkbox.

Checkboxes are on/off switches that may be toggled by the user. A
switch is "on" when the control element's checked attribute is set.
When a form is submitted, only "on" checkbox controls can become
successful.

Several checkboxes in a form may share the same control name. Thus,
for example, checkboxes allow users to select several values for the
same property. One CHECKBOX object only displays one checkbox in the
form.


.. _reference-checkbox-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string / cObject

         ->label

   Description
         The value of the label of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            label = TEXT
            label {
                value = First name
            }

         **Example:**

         ::

            label = First name

         **Example:**

         ::

            label.value = First name



.. _reference-checkbox-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <label />
                <input />
            )

   Default
         <label />

         <input />



.. _reference-checkbox-acceskey:

acceskey
''''''''

.. container:: table-row

   Property
         acceskey

   Data type
         string

   Description
         This attribute assigns an access key to an element.

         An access key is a single character from the document character set.

         Note. Authors should consider the input method of the expected reader
         when specifying an accesskey.

         Pressing an access key assigned to an element gives focus to the
         element.

         The action that occurs when an element receives focus depends on the
         element. For example, when a user activates a link defined by the A
         element, the user agent generally follows the link. When a user
         activates a radio button, the user agent changes the value of the
         radio button. When the user activates a text field, it allows input,
         etc.



.. _reference-checkbox-alt:

alt
'''

.. container:: table-row

   Property
         alt

   Data type
         string

   Description
         For user agents that cannot display images, forms, or applets, this
         attribute specifies alternate text. The language of the alternate text
         is specified by the lang attribute.



.. _reference-checkbox-checked:

checked
'''''''

.. container:: table-row

   Property
         checked

   Data type
         boolean/checked

   Description
         When the type attribute has the value "radio" or "checkbox", this
         boolean attribute specifies that the button is on.

         User agents must ignore this attribute for other control types.

         **Examples:**

         ::

            checked = 1
            checked = 0
            checked = checked



.. _reference-checkbox-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-checkbox-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.



.. _reference-checkbox-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-checkbox-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="click">Push this button</label>
            <input type="button" id="click" value="Click me" />



.. _reference-checkbox-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-checkbox-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="checkbox" name="tx_form[21]" value="click" />



.. _reference-checkbox-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-checkbox-tabindex:

tabindex
''''''''

.. container:: table-row

   Property
         tabindex

   Data type
         integer

   Description
         This attribute specifies the position of the current element in the
         tabbing order for the current document. This value must be a number
         between 0 and 32767. User agents should ignore leading zeros.

         The tabbing order defines the order in which elements will receive
         focus when navigated by the user via the keyboard. The tabbing order
         may include elements nested within other elements.

         Elements that may receive focus should be navigated by user agents
         according to the following rules:

         #. Those elements that support the tabindex attribute and assign a
            positive value to it are navigated first. Navigation proceeds from the
            element with the lowest tabindex value to the element with the highest
            value. Values need not be sequential nor must they begin with any
            particular value. Elements that have identical tabindex values should
            be navigated in the order they appear in the character stream.

         #. Those elements that do not support the tabindex attribute or support
            it and assign it a value of "0" are navigated next. These elements are
            navigated in the order they appear in the character stream.

         #. Elements that are disabled do not participate in the tabbing order.

         The actual key sequence that causes tabbing navigation or element
         activation depends on the configuration of the user agent (e.g., the
         "tab" key is used for navigation and the "enter" key is used to
         activate a selected element)

         User agents may also define key sequences to navigate the tabbing
         order in reverse. When the end (or beginning) of the tabbing order is
         reached, user agents may circle back to the beginning (or end).



.. _reference-checkbox-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.



.. _reference-checkbox-type:

type
''''

.. container:: table-row

   Property
         type

   Data type
         string

   Description
         Defines the type of form input control to create.

   Default
         checkbox



.. _reference-checkbox-value:

value
'''''

.. container:: table-row

   Property
         value

   Data type
         string

   Description
         This attribute assigns the initial value to the object


[tsref:(cObject).FORM.FormObject.CHECKBOX]


.. _reference-fieldset:

FIELDSET
~~~~~~~~

The FIELDSET element allows authors to group thematically related
controls and labels. Grouping controls makes it easier for users to
understand their purpose while simultaneously facilitating tabbing
navigation for visual user agents and speech navigation for speech-
oriented user agents. The proper use of this element makes documents
more accessible.


.. _reference-fieldset-1-2-3-4:

1, 2, 3, 4 ...
''''''''''''''

.. container:: table-row

   Property
         1, 2, 3, 4 ...

   Data type
         [array of FORM objects]

   Description
         FORM objects that are part of the SELECT.



.. _reference-fieldset-legend:

legend
''''''

.. container:: table-row

   Property
         legend

   Data type
         string / cObject

         ->legend

   Description
         The value of the legend of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            legend = TEXT
            legend {
                value = Personal information
            }

         **Example:**

         ::

            legend = Personal information

         **Example:**

         ::

            legend.value = Personal information



.. _reference-fieldset-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the FIELDSET object. The

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <fieldset>
                        <legend />
                        <containerWrap />
                </fieldset>
            )

   Default
         <fieldset> <legend /> <containerWrap /> </fieldset>



.. _reference-fieldset-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-fieldset-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.



.. _reference-fieldset-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="click">Push this button</label>
            <input type="button" id="click" value="Click me" />



.. _reference-fieldset-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-fieldset-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.


[tsref:(cObject).FORM.FormObject.FIELDSET]


.. _reference-fileupload:

FILEUPLOAD
~~~~~~~~~~

Creates a file select control. User agents may use the value of the
value attribute as the initial file name.

This control type allows the user to select files so that their
contents may be submitted with a form.

This object is still under development and probably all functionality
will be added after the first release of this extension. Currently it
is only possible to generate a field meant for file upload, but the
uploaded data will not be handled.


.. _reference-fileupload-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string / cObject

         ->label

   Description
         The value of the label of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            label = TEXT
            label {
                value = First name
            }

         **Example:**

         ::

            label = First name

         **Example:**

         ::

            label.value = First name



.. _reference-fileupload-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <label />
                <input />
            )

   Default
         <label />

         <input />



.. _reference-fileupload-acceskey:

acceskey
''''''''

.. container:: table-row

   Property
         acceskey

   Data type
         string

   Description
         This attribute assigns an access key to an element.

         An access key is a single character from the document character set.

         Note. Authors should consider the input method of the expected reader
         when specifying an accesskey.

         Pressing an access key assigned to an element gives focus to the
         element.

         The action that occurs when an element receives focus depends on the
         element. For example, when a user activates a link defined by the A
         element, the user agent generally follows the link. When a user
         activates a radio button, the user agent changes the value of the
         radio button. When the user activates a text field, it allows input,
         etc.



.. _reference-fileupload-alt:

alt
'''

.. container:: table-row

   Property
         alt

   Data type
         string

   Description
         For user agents that cannot display images, forms, or applets, this
         attribute specifies alternate text. The language of the alternate text
         is specified by the lang attribute.



.. _reference-fileupload-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-fileupload-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.



.. _reference-fileupload-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-fileupload-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="fileupload">Upload a document</label>
            <input type="file" id="fileupload" />



.. _reference-fileupload-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-fileupload-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="file" name="tx_form[21]" />



.. _reference-fileupload-size:

size
''''

.. container:: table-row

   Property
         size

   Data type
         integer

   Description
         This attribute tells the user agent the initial width of the control.
         The width is given in pixels.



.. _reference-fileupload-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-fileupload-tabindex:

tabindex
''''''''

.. container:: table-row

   Property
         tabindex

   Data type
         integer

   Description
         This attribute specifies the position of the current element in the
         tabbing order for the current document. This value must be a number
         between 0 and 32767. User agents should ignore leading zeros.

         The tabbing order defines the order in which elements will receive
         focus when navigated by the user via the keyboard. The tabbing order
         may include elements nested within other elements.

         Elements that may receive focus should be navigated by user agents
         according to the following rules:

         #. Those elements that support the tabindex attribute and assign a
            positive value to it are navigated first. Navigation proceeds from the
            element with the lowest tabindex value to the element with the highest
            value. Values need not be sequential nor must they begin with any
            particular value. Elements that have identical tabindex values should
            be navigated in the order they appear in the character stream.

         #. Those elements that do not support the tabindex attribute or support
            it and assign it a value of "0" are navigated next. These elements are
            navigated in the order they appear in the character stream.

         #. Elements that are disabled do not participate in the tabbing order.

         The actual key sequence that causes tabbing navigation or element
         activation depends on the configuration of the user agent (e.g., the
         "tab" key is used for navigation and the "enter" key is used to
         activate a selected element)

         User agents may also define key sequences to navigate the tabbing
         order in reverse. When the end (or beginning) of the tabbing order is
         reached, user agents may circle back to the beginning (or end).



.. _reference-fileupload-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.



.. _reference-fileupload-type:

type
''''

.. container:: table-row

   Property
         type

   Data type
         string

   Description
         Defines the type of form input control to create.

   Default
         file


[tsref:(cObject).FORM.FormObject.FILEUPLOAD]


.. _reference-hidden:

HIDDEN
~~~~~~

Creates a hidden control.

Authors may create controls that are not rendered but whose values are
submitted with a form. Authors generally use this control type to
store information between client/server exchanges that would otherwise
be lost due to the stateless nature of HTTP (see [RFC2616]).


.. _reference-hidden-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <input />
            )

   Default
         <input />



.. _reference-hidden-filters:

filters
'''''''

.. container:: table-row

   Property
         filters

   Data type
         [array of numbers]

         ->filters

   Description
         Add filters to the FORM object

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
                1 (
                    allowWhiteSpace = 1
                )
                2 = titlecase
            }

         **Submitted data:** john doe3

         **Filtered:** John Doe

         **Note:**

         All submitted data will be filtered by a Cross Site Scripting (XSS)
         filter by default to prevent this security issue.

   Default
         ::

			 filters {
			 	0 = removexss
			 }



.. _reference-hidden-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-hidden-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         ::

            <input type="hidden" id="hiddenfield" />



.. _reference-hidden-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-hidden-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="hidden" name="tx_form[21]" />



.. _reference-hidden-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-hidden-type:

type
''''

.. container:: table-row

   Property
         type

   Data type
         string

   Description
         Defines the type of form input control to create.

   Default
         hidden



.. _reference-hidden-value:

value
'''''

.. container:: table-row

   Property
         value

   Data type
         string

   Description
         This attribute assigns the initial value to the object.


[tsref:(cObject).FORM.FormObject.HIDDEN]


.. _reference-imagebutton:

IMAGEBUTTON
~~~~~~~~~~~

Creates a graphical submit button. The value of the src attribute
specifies the URI of the image that will decorate the button. For
accessibility reasons, authors should provide alternate text for the
image via the alt attribute.

When a pointing device is used to click on the image, the form is
submitted and the click coordinates passed to the server. The x value
is measured in pixels from the left of the image, and the y value in
pixels from the top of the image. The submitted data includes
name.x=x-value and name.y=y-value where "name" is the value of the
name attribute, and x-value and y-value are the x and y coordinate
values, respectively.

If the server takes different actions depending on the location
clicked, users of non-graphical browsers will be disadvantaged. For
this reason, authors should consider alternate approaches:

- Use multiple submit buttons (each with its own image) in place of a
  single graphical submit button. Authors may use style sheets to
  control the positioning of these buttons.

- Use a client-side image map together with scripting.


.. _reference-imagebutton-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string / cObject

         ->label

   Description
         The value of the label of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            label = TEXT
            label {
                value = First name
            }

         **Example:**

         ::

            label = First name

         **Example:**

         ::

            label.value = First name



.. _reference-imagebutton-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <label />
                <input />
            )

   Default
         <label />

         <input />



.. _reference-imagebutton-acceskey:

acceskey
''''''''

.. container:: table-row

   Property
         acceskey

   Data type
         string

   Description
         This attribute assigns an access key to an element.

         An access key is a single character from the document character set.

         Note. Authors should consider the input method of the expected reader
         when specifying an accesskey.

         Pressing an access key assigned to an element gives focus to the
         element.

         The action that occurs when an element receives focus depends on the
         element. For example, when a user activates a link defined by the A
         element, the user agent generally follows the link. When a user
         activates a radio button, the user agent changes the value of the
         radio button. When the user activates a text field, it allows input,
         etc.



.. _reference-imagebutton-alt:

alt
'''

.. container:: table-row

   Property
         alt

   Data type
         string

   Description
         For user agents that cannot display images, forms, or applets, this
         attribute specifies alternate text. The language of the alternate text
         is specified by the lang attribute.



.. _reference-imagebutton-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-imagebutton-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.



.. _reference-imagebutton-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-imagebutton-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="fileupload">Upload a document</label>
            <input type="file" id="fileupload" />



.. _reference-imagebutton-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-imagebutton-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="file" name="tx_form[21]" />



.. _reference-imagebutton-src:

src
'''

.. container:: table-row

   Property
         src

   Data type
         imgResource

   Description
         This attribute specifies the location of the image to be used to
         decorate the graphical submit button.

         Because this is an :ref:`imgResource <t3tsref:imgresource>`,
         it also accepts :ref:`GIFBUILDER <t3tsref:gifbuilder>` objects.



.. _reference-imagebutton-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-imagebutton-tabindex:

tabindex
''''''''

.. container:: table-row

   Property
         tabindex

   Data type
         integer

   Description
         This attribute specifies the position of the current element in the
         tabbing order for the current document. This value must be a number
         between 0 and 32767. User agents should ignore leading zeros.

         The tabbing order defines the order in which elements will receive
         focus when navigated by the user via the keyboard. The tabbing order
         may include elements nested within other elements.

         Elements that may receive focus should be navigated by user agents
         according to the following rules:

         #. Those elements that support the tabindex attribute and assign a
            positive value to it are navigated first. Navigation proceeds from the
            element with the lowest tabindex value to the element with the highest
            value. Values need not be sequential nor must they begin with any
            particular value. Elements that have identical tabindex values should
            be navigated in the order they appear in the character stream.

         #. Those elements that do not support the tabindex attribute or support
            it and assign it a value of "0" are navigated next. These elements are
            navigated in the order they appear in the character stream.

         #. Elements that are disabled do not participate in the tabbing order.

         The actual key sequence that causes tabbing navigation or element
         activation depends on the configuration of the user agent (e.g., the
         "tab" key is used for navigation and the "enter" key is used to
         activate a selected element)

         User agents may also define key sequences to navigate the tabbing
         order in reverse. When the end (or beginning) of the tabbing order is
         reached, user agents may circle back to the beginning (or end).



.. _reference-imagebutton-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.



.. _reference-imagebutton-type:

type
''''

.. container:: table-row

   Property
         type

   Data type
         string

   Description
         Defines the type of form input control to create.

   Default
         image



.. _reference-imagebutton-value:

value
'''''

.. container:: table-row

   Property
         value

   Data type
         string

   Description
         This attribute assigns the initial value to the object.


[tsref:(cObject).FORM.FormObject.IMAGEBUTTON]


.. _reference-form-optgroup:

OPTGROUP
~~~~~~~~

The OPTGROUP element allows authors to group choices logically. This
is particularly helpful when the user must choose from a long list of
options; groups of related choices are easier to grasp and remember
than a single long list of options. All OPTGROUP elements must be
specified directly within a SELECT element (i.e., groups may not be
nested).

An OPTGROUP object can only exist between a SELECT object.


.. _reference-optgroup-1-2-3-4:

1, 2, 3, 4 ...
''''''''''''''

.. container:: table-row

   Property
         1, 2, 3, 4 ...

   Data type
         [array of FORM objects]

   Description
         OPTION objects, part of the OPTGROUP



.. _reference-optgroup-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <optgroup>
                        <elements />
                </optgroup>
            )

   Default
         <optgroup>

         <elements />

         </optgroup>



.. _reference-optgroup-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-optgroup-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-optgroup-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         ::

            <input type="file" id="fileupload" />



.. _reference-optgroup-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string

   Description
         This attribute specifies the displayed label of the OPTGROUP

   Default
         optgroup



.. _reference-optgroup-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-optgroup-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-optgroup-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.


[tsref:(cObject).FORM.FormObject.OPTGROUP]


.. _reference-option:

OPTION
~~~~~~

Defines elements in a select or drop-down list.

An OPTION object can only exist between a SELECT or OPTGROUP object.


.. _reference-option-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <option>
                        <optionvalue />
                </option>
            )

   Default
         <option> <optionvalue /> </option>



.. _reference-option-data:

data
''''

.. container:: table-row

   Property
         data

   Data type
         string

   Description
         The contents of the OPTION



.. _reference-option-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-option-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-option-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="fileupload">Upload a document</label>
            <input type="file" id="fileupload" />



.. _reference-option-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string

   Description
         This attribute allows authors to specify a shorter label for an option
         than the content of the OPTION element. When specified, user agents
         should use the value of this attribute rather than the content of the
         OPTION element as the option label.



.. _reference-option-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-option-selected:

selected
''''''''

.. container:: table-row

   Property
         selected

   Data type
         boolean/selected

   Description
         When set, this boolean attribute specifies that this option is pre-
         selected.

         **Examples:**

         ::

            selected = 1
            selected = 0
            selected = selected



.. _reference-option-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-option-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.



.. _reference-option-value:

value
'''''

.. container:: table-row

   Property
         value

   Data type
         string

   Description
         This attribute assigns the initial value to the object.


[tsref:(cObject).FORM.FormObject.OPTION]


.. _reference-password:

PASSWORD
~~~~~~~~

Creates a single-line text input control, but the input text is
rendered in such a way as to hide the characters (e.g., a series of
asterisks). This control type is often used for sensitive input such
as passwords. Note that the current value is the text entered by the
user, not the text rendered by the user agent.

**Note** . Form designers should note that this mechanism affords only
light security protection. Although the password is masked by user
agents from casual observers, it is transmitted to the server in clear
text, and may be read by anyone with low-level access to the network.


.. _reference-password-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string / cObject

         ->label

   Description
         The value of the label of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            label = TEXT
            label {
                value = First name
            }

         **Example:**

         ::

            label = First name

         **Example:**

         ::

            label.value = First name



.. _reference-password-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <label />
                <input />
            )

   Default
         <label />

         <input />



.. _reference-password-filters:

filters
'''''''

.. container:: table-row

   Property
         filters

   Data type
         [array of numbers]

         ->filters

   Description
         Add filters to the FORM object

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

   Default
         ::

			 filters {
			 	0 = removexss
			 }



.. _reference-password-acceskey:

acceskey
''''''''

.. container:: table-row

   Property
         acceskey

   Data type
         string

   Description
         This attribute assigns an access key to an element.

         An access key is a single character from the document character set.

         Note. Authors should consider the input method of the expected reader
         when specifying an accesskey.

         Pressing an access key assigned to an element gives focus to the
         element.

         The action that occurs when an element receives focus depends on the
         element. For example, when a user activates a link defined by the A
         element, the user agent generally follows the link. When a user
         activates a radio button, the user agent changes the value of the
         radio button. When the user activates a text field, it allows input,
         etc.



.. _reference-password-alt:

alt
'''

.. container:: table-row

   Property
         alt

   Data type
         string

   Description
         For user agents that cannot display images, forms, or applets, this
         attribute specifies alternate text. The language of the alternate text
         is specified by the lang attribute.



.. _reference-password-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-password-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.



.. _reference-password-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-password-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="passwordfield">Password:</label>
            <input type="password" id="passwordfield" />



.. _reference-password-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-password-maxlength:

maxlength
'''''''''

.. container:: table-row

   Property
         maxlength

   Data type
         integer

   Description
         This attribute specifies the maximum number of characters the user may
         enter. This number may exceed the specified size, in which case the
         user agent should offer a scrolling mechanism. The default value for
         this attribute is an unlimited number.



.. _reference-password-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="password" name="tx_form[21]" />



.. _reference-password-readonly:

readonly
''''''''

.. container:: table-row

   Property
         readonly

   Data type
         boolean/readonly

   Description
         When set for a form control, this boolean attribute prohibits changes
         to the control.

         The readonly attribute specifies whether the control may be modified
         by the user.

         When set, the readonly attribute has the following effects on an
         element:

         - Read-only elements receive focus but cannot be modified by the user.

         - Read-only elements are included in tabbing navigation.

         - Read-only elements may be successful.

         How read-only elements are rendered depends on the user agent.

         **Examples:**

         ::

            readonly = 1
            readonly = 0
            readonly = disabled

         **Note** . The only way to modify dynamically the value of the
         readonly attribute is through a script.



.. _reference-password-size:

size
''''

.. container:: table-row

   Property
         size

   Data type
         integer

   Description
         This attribute tells the user agent the initial width of the control.
         The value refers to the (integer) number of characters.



.. _reference-password-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-password-tabindex:

tabindex
''''''''

.. container:: table-row

   Property
         tabindex

   Data type
         integer

   Description
         This attribute specifies the position of the current element in the
         tabbing order for the current document. This value must be a number
         between 0 and 32767. User agents should ignore leading zeros.

         The tabbing order defines the order in which elements will receive
         focus when navigated by the user via the keyboard. The tabbing order
         may include elements nested within other elements.

         Elements that may receive focus should be navigated by user agents
         according to the following rules:

         #. Those elements that support the tabindex attribute and assign a
            positive value to it are navigated first. Navigation proceeds from the
            element with the lowest tabindex value to the element with the highest
            value. Values need not be sequential nor must they begin with any
            particular value. Elements that have identical tabindex values should
            be navigated in the order they appear in the character stream.

         #. Those elements that do not support the tabindex attribute or support
            it and assign it a value of "0" are navigated next. These elements are
            navigated in the order they appear in the character stream.

         #. Elements that are disabled do not participate in the tabbing order.

         The actual key sequence that causes tabbing navigation or element
         activation depends on the configuration of the user agent (e.g., the
         "tab" key is used for navigation and the "enter" key is used to
         activate a selected element)

         User agents may also define key sequences to navigate the tabbing
         order in reverse. When the end (or beginning) of the tabbing order is
         reached, user agents may circle back to the beginning (or end).



.. _reference-password-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.



.. _reference-password-type:

type
''''

.. container:: table-row

   Property
         type

   Data type
         string

   Description
         Defines the type of form input control to create.

   Default
         password



.. _reference-password-value:

value
'''''

.. container:: table-row

   Property
         value

   Data type
         string

   Description
         This attribute assigns the initial value to the object.


[tsref:(cObject).FORM.FormObject.PASSWORD]


.. _reference-radio:

RADIO
~~~~~

Creates a radio button.

Radio buttons are on/off switches that may be toggled by the user. A
switch is "on" when the control element's checked attribute is set.
When a form is submitted, only "on" radio button controls can become
successful.

Several radio buttons in a form may share the same control name. Thus,
for example, radio buttons allow users to select several values for
the same property.

Radio buttons are like checkboxes except that when several share the
same control name, they are mutually exclusive: when one is switched
"on", all others with the same name are switched "off".

Radio buttons are normally grouped in a FIELDSET object.

**Note from W3C for user agent behaviour** : If no radio button in a
set sharing the same control name is initially "on", user agent
behavior for choosing which control is initially "on" is undefined.
Note. Since existing implementations handle this case differently, the
current specification differs from RFC 1866 ([RFC1866] section
8.1.2.4), which states:

At all times, exactly one of the radio buttons in a set is checked. If
none of the elements of a set of radio buttons specifies \`checked',
then the user agent must check the first radio button of the set
initially.

Since user agent behavior differs, authors should ensure that in each
set of radio buttons that one is initially "on".


.. _reference-radio-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string / cObject

         ->label

   Description
         The value of the label of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            label = TEXT
            label {
                value = First name
            }

         **Example:**

         ::

            label = First name

         **Example:**

         ::

            label.value = First name



.. _reference-radio-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <label />
                <input />
            )

   Default
         <label />

         <input />



.. _reference-radio-acceskey:

acceskey
''''''''

.. container:: table-row

   Property
         acceskey

   Data type
         string

   Description
         This attribute assigns an access key to an element.

         An access key is a single character from the document character set.

         Note. Authors should consider the input method of the expected reader
         when specifying an accesskey.

         Pressing an access key assigned to an element gives focus to the
         element.

         The action that occurs when an element receives focus depends on the
         element. For example, when a user activates a link defined by the A
         element, the user agent generally follows the link. When a user
         activates a radio button, the user agent changes the value of the
         radio button. When the user activates a text field, it allows input,
         etc.



.. _reference-radio-alt:

alt
'''

.. container:: table-row

   Property
         alt

   Data type
         string

   Description
         For user agents that cannot display images, forms, or applets, this
         attribute specifies alternate text. The language of the alternate text
         is specified by the lang attribute.



.. _reference-radio-checked:

checked
'''''''

.. container:: table-row

   Property
         checked

   Data type
         boolean/checked

   Description
         When the type attribute has the value "radio" or "checkbox", this
         boolean attribute specifies that the button is on.

         User agents must ignore this attribute for other control types.

         **Examples:**

         ::

            checked = 1
            checked = 0
            checked = checked



.. _reference-radio-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-radio-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.



.. _reference-radio-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-radio-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="female">Female</label>
            <input type="radio" id="female" value="1" />



.. _reference-radio-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-radio-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="checkbox" name="tx_form[21]" value="click" />



.. _reference-radio-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-radio-tabindex:

tabindex
''''''''

.. container:: table-row

   Property
         tabindex

   Data type
         integer

   Description
         This attribute specifies the position of the current element in the
         tabbing order for the current document. This value must be a number
         between 0 and 32767. User agents should ignore leading zeros.

         The tabbing order defines the order in which elements will receive
         focus when navigated by the user via the keyboard. The tabbing order
         may include elements nested within other elements.

         Elements that may receive focus should be navigated by user agents
         according to the following rules:

         #. Those elements that support the tabindex attribute and assign a
            positive value to it are navigated first. Navigation proceeds from the
            element with the lowest tabindex value to the element with the highest
            value. Values need not be sequential nor must they begin with any
            particular value. Elements that have identical tabindex values should
            be navigated in the order they appear in the character stream.

         #. Those elements that do not support the tabindex attribute or support
            it and assign it a value of "0" are navigated next. These elements are
            navigated in the order they appear in the character stream.

         #. Elements that are disabled do not participate in the tabbing order.

         The actual key sequence that causes tabbing navigation or element
         activation depends on the configuration of the user agent (e.g., the
         "tab" key is used for navigation and the "enter" key is used to
         activate a selected element)

         User agents may also define key sequences to navigate the tabbing
         order in reverse. When the end (or beginning) of the tabbing order is
         reached, user agents may circle back to the beginning (or end).



.. _reference-radio-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.



.. _reference-radio-type:

type
''''

.. container:: table-row

   Property
         type

   Data type
         string

   Description
         Defines the type of form input control to create.

   Default
         radio



.. _reference-radio-value:

value
'''''

.. container:: table-row

   Property
         value

   Data type
         string

   Description
         This attribute assigns the initial value to the object


[tsref:(cObject).FORM.FormObject.RADIO]


.. _reference-reset:

RESET
~~~~~

Creates a reset button.

When activated, a reset button resets all controls to their initial
values.


.. _reference-reset-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string / cObject

         ->label

   Description
         The value of the label of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            label = TEXT
            label {
                value = First name
            }

         **Example:**

         ::

            label = First name

         **Example:**

         ::

            label.value = First name



.. _reference-reset-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <label />
                <input />
            )

   Default
         <label />

         <input />



.. _reference-reset-acceskey:

acceskey
''''''''

.. container:: table-row

   Property
         acceskey

   Data type
         string

   Description
         This attribute assigns an access key to an element.

         An access key is a single character from the document character set.

         Note. Authors should consider the input method of the expected reader
         when specifying an accesskey.

         Pressing an access key assigned to an element gives focus to the
         element.

         The action that occurs when an element receives focus depends on the
         element. For example, when a user activates a link defined by the A
         element, the user agent generally follows the link. When a user
         activates a radio button, the user agent changes the value of the
         radio button. When the user activates a text field, it allows input,
         etc.



.. _reference-reset-alt:

alt
'''

.. container:: table-row

   Property
         alt

   Data type
         string

   Description
         For user agents that cannot display images, forms, or applets, this
         attribute specifies alternate text. The language of the alternate text
         is specified by the lang attribute.



.. _reference-reset-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-reset-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.



.. _reference-reset-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-reset-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="click">Push this button</label>
            <input type="button" id="click" value="Click me" />



.. _reference-reset-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-reset-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="button" name="tx_form[21]" value="click" />



.. _reference-reset-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-reset-tabindex:

tabindex
''''''''

.. container:: table-row

   Property
         tabindex

   Data type
         integer

   Description
         This attribute specifies the position of the current element in the
         tabbing order for the current document. This value must be a number
         between 0 and 32767. User agents should ignore leading zeros.

         The tabbing order defines the order in which elements will receive
         focus when navigated by the user via the keyboard. The tabbing order
         may include elements nested within other elements.

         Elements that may receive focus should be navigated by user agents
         according to the following rules:

         #. Those elements that support the tabindex attribute and assign a
            positive value to it are navigated first. Navigation proceeds from the
            element with the lowest tabindex value to the element with the highest
            value. Values need not be sequential nor must they begin with any
            particular value. Elements that have identical tabindex values should
            be navigated in the order they appear in the character stream.

         #. Those elements that do not support the tabindex attribute or support
            it and assign it a value of "0" are navigated next. These elements are
            navigated in the order they appear in the character stream.

         #. Elements that are disabled do not participate in the tabbing order.

         The actual key sequence that causes tabbing navigation or element
         activation depends on the configuration of the user agent (e.g., the
         "tab" key is used for navigation and the "enter" key is used to
         activate a selected element)

         User agents may also define key sequences to navigate the tabbing
         order in reverse. When the end (or beginning) of the tabbing order is
         reached, user agents may circle back to the beginning (or end).



.. _reference-reset-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.



.. _reference-reset-type:

type
''''

.. container:: table-row

   Property
         type

   Data type
         string

   Description
         Defines the type of form input control to create.

   Default
         reset



.. _reference-reset-value:

value
'''''

.. container:: table-row

   Property
         value

   Data type
         string

   Description
         This attribute assigns the initial value to the object.


[tsref:(cObject).FORM.FormObject.RESET]


.. _reference-select:

SELECT
~~~~~~

The SELECT object creates a menu. Each choice offered by the menu is
represented by an OPTION object. A SELECT object must contain at least
one OPTION object

**Pre-selected options**

Zero or more choices may be pre-selected for the user. User agents
should determine which choices are pre-selected as follows:

- If no OPTION object has the selected attribute set, user agent
  behavior for choosing which option is initially selected is undefined.
  Note. Since existing implementations handle this case differently, the
  current specification differs from RFC 1866 ([RFC1866] section 8.1.3),
  which states:The initial state has the first option selected, unless a
  SELECTED attribute is present on any of the <OPTION> elements.Since
  user agent behavior differs, authors should ensure that each menu
  includes a default pre-selected OPTION.

- If one OPTION object has the selected attribute set, it should be pre-
  selected.

- If the SELECT object has the multiple attribute set and more than one
  OPTION object has the selected attribute set, they should all be pre-
  selected.

- It is considered an error if more than one OPTION object has the
  selected attribute set and the SELECT object does not have the
  multiple attribute set. User agents may vary in how they handle this
  error, but should not pre-select more than one choice.


.. _reference-select-1-2-3-4:

1, 2, 3, 4 ...
''''''''''''''

.. container:: table-row

   Property
         1, 2, 3, 4 ...

   Data type
         [array of FORM objects]

   Description
         OPTION and/or OPTGROUP objects, part of the SELECT



.. _reference-select-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string / cObject

         ->label

   Description
         The value of the label of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            label = TEXT
            label {
                value = First name
            }

         **Example:**

         ::

            label = First name

         **Example:**

         ::

            label.value = First name



.. _reference-select-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <label />
                <select>
                        <elements />
                </select>
            )

   Default
         <label /> <select>

         <elements />

         </select>



.. _reference-select-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-select-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-select-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="click">Push this button</label>
            <input type="button" id="click" value="Click me" />



.. _reference-select-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-select-multiple:

multiple
''''''''

.. container:: table-row

   Property
         multiple

   Data type
         boolean/multiple

   Description
         If set, this boolean attribute allows multiple selections.

         If not set, the SELECT element only permits single selections.

         **Examples:**

         ::

            multiple = 1
            multiple = 0
            multiple = multiple



.. _reference-select-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="button" name="tx_form[21]" value="click" />



.. _reference-select-size:

size
''''

.. container:: table-row

   Property
         size

   Data type
         integer

   Description
         If a SELECT object is presented as a scrolled list box, this attribute
         specifies the number of rows in the list that should be visible at the
         same time. Visual user agents are not required to present a SELECT
         object as a list box; they may use any other mechanism, such as a
         drop-down menu.



.. _reference-select-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-select-tabindex:

tabindex
''''''''

.. container:: table-row

   Property
         tabindex

   Data type
         integer

   Description
         This attribute specifies the position of the current element in the
         tabbing order for the current document. This value must be a number
         between 0 and 32767. User agents should ignore leading zeros.

         The tabbing order defines the order in which elements will receive
         focus when navigated by the user via the keyboard. The tabbing order
         may include elements nested within other elements.

         Elements that may receive focus should be navigated by user agents
         according to the following rules:

         #. Those elements that support the tabindex attribute and assign a
            positive value to it are navigated first. Navigation proceeds from the
            element with the lowest tabindex value to the element with the highest
            value. Values need not be sequential nor must they begin with any
            particular value. Elements that have identical tabindex values should
            be navigated in the order they appear in the character stream.

         #. Those elements that do not support the tabindex attribute or support
            it and assign it a value of "0" are navigated next. These elements are
            navigated in the order they appear in the character stream.

         #. Elements that are disabled do not participate in the tabbing order.

         The actual key sequence that causes tabbing navigation or element
         activation depends on the configuration of the user agent (e.g., the
         "tab" key is used for navigation and the "enter" key is used to
         activate a selected element)

         User agents may also define key sequences to navigate the tabbing
         order in reverse. When the end (or beginning) of the tabbing order is
         reached, user agents may circle back to the beginning (or end).



.. _reference-select-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.


[tsref:(cObject).FORM.FormObject.SELECT]


.. _reference-submit:

SUBMIT
~~~~~~

Creates a submit button.

When activated, a submit button submits a form. A form may contain
more than one submit button.


.. _reference-submit-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string / cObject

         ->label

   Description
         The value of the label of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            label = TEXT
            label {
                value = First name
            }

         **Example:**

         ::

            label = First name

         **Example:**

         ::

            label.value = First name



.. _reference-submit-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <label />
                <input />
            )

   Default
         <label />

         <input />



.. _reference-submit-acceskey:

acceskey
''''''''

.. container:: table-row

   Property
         acceskey

   Data type
         string

   Description
         This attribute assigns an access key to an element.

         An access key is a single character from the document character set.

         Note. Authors should consider the input method of the expected reader
         when specifying an accesskey.

         Pressing an access key assigned to an element gives focus to the
         element.

         The action that occurs when an element receives focus depends on the
         element. For example, when a user activates a link defined by the A
         element, the user agent generally follows the link. When a user
         activates a radio button, the user agent changes the value of the
         radio button. When the user activates a text field, it allows input,
         etc.



.. _reference-submit-alt:

alt
'''

.. container:: table-row

   Property
         alt

   Data type
         string

   Description
         For user agents that cannot display images, forms, or applets, this
         attribute specifies alternate text. The language of the alternate text
         is specified by the lang attribute.



.. _reference-submit-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-submit-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.



.. _reference-submit-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-submit-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="click">Push this button</label>
            <input type="button" id="click" value="Click me" />



.. _reference-submit-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-submit-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="button" name="tx_form[21]" value="click" />



.. _reference-submit-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-submit-tabindex:

tabindex
''''''''

.. container:: table-row

   Property
         tabindex

   Data type
         integer

   Description
         This attribute specifies the position of the current element in the
         tabbing order for the current document. This value must be a number
         between 0 and 32767. User agents should ignore leading zeros.

         The tabbing order defines the order in which elements will receive
         focus when navigated by the user via the keyboard. The tabbing order
         may include elements nested within other elements.

         Elements that may receive focus should be navigated by user agents
         according to the following rules:

         #. Those elements that support the tabindex attribute and assign a
            positive value to it are navigated first. Navigation proceeds from the
            element with the lowest tabindex value to the element with the highest
            value. Values need not be sequential nor must they begin with any
            particular value. Elements that have identical tabindex values should
            be navigated in the order they appear in the character stream.

         #. Those elements that do not support the tabindex attribute or support
            it and assign it a value of "0" are navigated next. These elements are
            navigated in the order they appear in the character stream.

         #. Elements that are disabled do not participate in the tabbing order.

         The actual key sequence that causes tabbing navigation or element
         activation depends on the configuration of the user agent (e.g., the
         "tab" key is used for navigation and the "enter" key is used to
         activate a selected element)

         User agents may also define key sequences to navigate the tabbing
         order in reverse. When the end (or beginning) of the tabbing order is
         reached, user agents may circle back to the beginning (or end).



.. _reference-submit-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.



.. _reference-submit-type:

type
''''

.. container:: table-row

   Property
         type

   Data type
         string

   Description
         Defines the type of form input control to create.

   Default
         submit



.. _reference-submit-value:

value
'''''

.. container:: table-row

   Property
         value

   Data type
         string

   Description
         This attribute assigns the initial value to the object.


[tsref:(cObject).FORM.FormObject.SUBMIT]


.. _reference-textarea:

TEXTAREA
~~~~~~~~

The TEXTAREA object creates a multi-line text input control. User
agents should use the contents of this object as the initial value of
the control and should render this text initially.


.. _reference-textarea-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string / cObject

         ->label

   Description
         The value of the label of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            label = TEXT
            label {
                value = First name
            }

         **Example:**

         ::

            label = First name

         **Example:**

         ::

            label.value = First name



.. _reference-textarea-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <label />
                <textarea />
            )

   Default
         <label />

         <textarea />



.. _reference-textarea-data:

data
''''

.. container:: table-row

   Property
         data

   Data type
         string

   Description
         The contents of the TEXTAREA



.. _reference-textarea-filters:

filters
'''''''

.. container:: table-row

   Property
         filters

   Data type
         [array of numbers]

         ->filters

   Description
         Add filters to the FORM object

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
                1 (
                    allowWhiteSpace = 1
                )
                2 = titlecase
            }

         **Submitted data:** john doe3

         **Filtered:** John Doe

         **Note:**

         All submitted data will be filtered by a Cross Site Scripting (XSS)
         filter by default to prevent this security issue.

   Default
         ::

			 filters {
			 	0 = removexss
			 }



.. _reference-textarea-acceskey:

acceskey
''''''''

.. container:: table-row

   Property
         acceskey

   Data type
         string

   Description
         This attribute assigns an access key to an element.

         An access key is a single character from the document character set.

         Note. Authors should consider the input method of the expected reader
         when specifying an accesskey.

         Pressing an access key assigned to an element gives focus to the
         element.

         The action that occurs when an element receives focus depends on the
         element. For example, when a user activates a link defined by the A
         element, the user agent generally follows the link. When a user
         activates a radio button, the user agent changes the value of the
         radio button. When the user activates a text field, it allows input,
         etc.



.. _reference-textarea-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-textarea-cols:

cols
''''

.. container:: table-row

   Property
         cols

   Data type
         integer

   Description
         This attribute specifies the visible width in average character
         widths.

         Users should be able to enter longer lines than this, so user agents
         should provide some means to scroll through the contents of the
         control when the contents extend beyond the visible area. User agents
         may wrap visible text lines to keep long lines visible without the
         need for scrolling.

   Default
         40



.. _reference-textarea-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.



.. _reference-textarea-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-textarea-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="click">Push this button</label>
            <input type="button" id="click" value="Click me" />



.. _reference-textarea-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-textarea-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="button" name="tx_form[21]" value="click" />



.. _reference-textarea-readonly:

readonly
''''''''

.. container:: table-row

   Property
         readonly

   Data type
         boolean/readonly

   Description
         When set for a form control, this boolean attribute prohibits changes
         to the control.

         The readonly attribute specifies whether the control may be modified
         by the user.

         When set, the readonly attribute has the following effects on an
         element:

         - Read-only elements receive focus but cannot be modified by the user.

         - Read-only elements are included in tabbing navigation.

         - Read-only elements may be successful.

         How read-only elements are rendered depends on the user agent.

         **Examples:**

         ::

            readonly = 1
            readonly = 0
            readonly = disabled

         **Note** . The only way to modify dynamically the value of the
         readonly attribute is through a script.



.. _reference-textarea-rows:

rows
''''

.. container:: table-row

   Property
         rows

   Data type
         integer

   Description
         This attribute specifies the number of visible text lines.

         Users should be able to enter more lines than this, so user agents
         should provide some means to scroll through the contents of the
         control when the contents extend beyond the visible area.

   Default
         5



.. _reference-textarea-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-textarea-tabindex:

tabindex
''''''''

.. container:: table-row

   Property
         tabindex

   Data type
         integer

   Description
         This attribute specifies the position of the current element in the
         tabbing order for the current document. This value must be a number
         between 0 and 32767. User agents should ignore leading zeros.

         The tabbing order defines the order in which elements will receive
         focus when navigated by the user via the keyboard. The tabbing order
         may include elements nested within other elements.

         Elements that may receive focus should be navigated by user agents
         according to the following rules:

         #. Those elements that support the tabindex attribute and assign a
            positive value to it are navigated first. Navigation proceeds from the
            element with the lowest tabindex value to the element with the highest
            value. Values need not be sequential nor must they begin with any
            particular value. Elements that have identical tabindex values should
            be navigated in the order they appear in the character stream.

         #. Those elements that do not support the tabindex attribute or support
            it and assign it a value of "0" are navigated next. These elements are
            navigated in the order they appear in the character stream.

         #. Elements that are disabled do not participate in the tabbing order.

         The actual key sequence that causes tabbing navigation or element
         activation depends on the configuration of the user agent (e.g., the
         "tab" key is used for navigation and the "enter" key is used to
         activate a selected element)

         User agents may also define key sequences to navigate the tabbing
         order in reverse. When the end (or beginning) of the tabbing order is
         reached, user agents may circle back to the beginning (or end).



.. _reference-textarea-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.


[tsref:(cObject).FORM.FormObject.TEXTAREA]


.. _reference-textline:

TEXTLINE
~~~~~~~~

Creates a single-line text input control.


.. _reference-textline-label:

label
'''''

.. container:: table-row

   Property
         label

   Data type
         string / cObject

         ->label

   Description
         The value of the label of the object.

         By default the value of the label is a TEXT cObj, but you can use
         other cObj as well. When no cObj type is used it assumes you want to
         use TEXT. In this case you can assign the value directly to the label
         property or indirectly to the value property of the label.

         For more information about cObjects, take a look in the document TSREF

         **Example:**

         ::

            label = TEXT
            label {
                value = First name
            }

         **Example:**

         ::

            label = First name

         **Example:**

         ::

            label.value = First name



.. _reference-textline-layout:

layout
''''''

.. container:: table-row

   Property
         layout

   Data type
         string

         ->layout

   Description
         Change the default layout for the object.

         This entered layout will only be used for the particular object where
         the layout override has been defined.

         **Example:**

         ::

            layout (
                <label />
                <input />
            )

   Default
         <label />

         <input />



.. _reference-textline-filters:

filters
'''''''

.. container:: table-row

   Property
         filters

   Data type
         [array of numbers]

         ->filters

   Description
         Add filters to the FORM object

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
                1 (
                    allowWhiteSpace = 1
                )
                2 = titlecase
            }

         **Submitted data:** john doe3

         **Filtered:** John Doe

         **Note:**

         All submitted data will be filtered by a Cross Site Scripting (XSS)
         filter by default to prevent this security issue.

   Default
         ::

			 filters {
				0 = removexss
			 }



.. _reference-textline-acceskey:

acceskey
''''''''

.. container:: table-row

   Property
         acceskey

   Data type
         string

   Description
         This attribute assigns an access key to an element.

         An access key is a single character from the document character set.

         Note. Authors should consider the input method of the expected reader
         when specifying an accesskey.

         Pressing an access key assigned to an element gives focus to the
         element.

         The action that occurs when an element receives focus depends on the
         element. For example, when a user activates a link defined by the A
         element, the user agent generally follows the link. When a user
         activates a radio button, the user agent changes the value of the
         radio button. When the user activates a text field, it allows input,
         etc.



.. _reference-textline-alt:

alt
'''

.. container:: table-row

   Property
         alt

   Data type
         string

   Description
         For user agents that cannot display images, forms, or applets, this
         attribute specifies alternate text. The language of the alternate text
         is specified by the lang attribute.



.. _reference-textline-class:

class
'''''

.. container:: table-row

   Property
         class

   Data type
         string

   Description
         This attribute assigns a class name or set of class names to an
         element.

         Any number of elements may be assigned the same class name or names.

         Multiple class names must be separated by white space characters.



.. _reference-textline-dir:

dir
'''

.. container:: table-row

   Property
         dir

   Data type
         ltr/rtl

   Description
         This attribute specifies the base direction of directionally neutral
         text (i.e., text that doesn't have inherent directionality as defined
         in[UNICODE]) in an element's content and attribute values.

         It also specifies the directionality of tables. Possible values:

         \* LTR: Left-to-right text or table.

         \* RTL: Right-to-left text or table.

         In addition to specifying the language of a document with the lang
         attribute, authors may need to specify the base directionality (left-
         to-right or right-to-left) of portions of a document's text, of table
         structure, etc. This is done with the dir attribute.



.. _reference-textline-disabled:

disabled
''''''''

.. container:: table-row

   Property
         disabled

   Data type
         boolean/disabled

   Description
         When set for a form control, this boolean attribute disables the
         control for user input.

         When set, the disabled attribute has the following effects on an
         element:

         Disabled controls do not receive focus.

         Disabled controls are skipped in tabbing navigation.

         Disabled controls cannot be successful.

         This attribute is inherited but local declarations override the
         inherited value.

         How disabled elements are rendered depends on the user agent. For
         example, some user agents "gray out" disabled menu items, button
         labels, etc.

         **Examples:**

         ::

            disabled = 1
            disabled = 0
            disabled = disabled



.. _reference-textline-id:

id
''

.. container:: table-row

   Property
         id

   Data type
         string

   Description
         This attribute assigns an id to an element.

         This id must be unique in a document.

         If an id has been assigned to the object and a value has been entered
         for the label, the "for" attribute will inherit the id.

         ::

            <label for="passwordfield">Password:</label>
            <input type="password" id="passwordfield" />



.. _reference-textline-lang:

lang
''''

.. container:: table-row

   Property
         lang

   Data type
         string

   Description
         This attribute specifies the base language of an element's attribute
         values and text content. The default value of this attribute is
         unknown.

         Briefly, language codes consist of a primary code and a possibly empty
         series of subcodes:

         language-code = primary-code ( "-" subcode )\*

         Here are some sample language codes:

         "en": English

         "en-US": the U.S. version of English.

         "en-cockney": the Cockney version of English.

         "i-navajo": the Navajo language spoken by some Native Americans.

         "x-klingon": The primary tag "x" indicates an experimental language
         tag



.. _reference-textline-maxlength:

maxlength
'''''''''

.. container:: table-row

   Property
         maxlength

   Data type
         integer

   Description
         This attribute specifies the maximum number of characters the user may
         enter. This number may exceed the specified size, in which case the
         user agent should offer a scrolling mechanism. The default value for
         this attribute is an unlimited number.



.. _reference-textline-name:

name
''''

.. container:: table-row

   Property
         name

   Data type
         string

   Description
         This attribute names the element so that submitted data can be
         identified by processing the form server side.

         If no name has been given, it will get assigned an internal counter
         together with the prefix, like:

         ::

            <input type="password" name="tx_form[21]" />



.. _reference-textline-readonly:

readonly
''''''''

.. container:: table-row

   Property
         readonly

   Data type
         boolean/readonly

   Description
         When set for a form control, this boolean attribute prohibits changes
         to the control.

         The readonly attribute specifies whether the control may be modified
         by the user.

         When set, the readonly attribute has the following effects on an
         element:

         - Read-only elements receive focus but cannot be modified by the user.

         - Read-only elements are included in tabbing navigation.

         - Read-only elements may be successful.

         How read-only elements are rendered depends on the user agent.

         **Examples:**

         ::

            readonly = 1
            readonly = 0
            readonly = disabled

         **Note** . The only way to modify dynamically the value of the
         readonly attribute is through a script.



.. _reference-textline-size:

size
''''

.. container:: table-row

   Property
         size

   Data type
         integer

   Description
         This attribute tells the user agent the initial width of the control.
         The value refers to the (integer) number of characters.



.. _reference-textline-style:

style
'''''

.. container:: table-row

   Property
         style

   Data type
         string

   Description
         This attribute specifies style information for the current element.



.. _reference-textline-tabindex:

tabindex
''''''''

.. container:: table-row

   Property
         tabindex

   Data type
         integer

   Description
         This attribute specifies the position of the current element in the
         tabbing order for the current document. This value must be a number
         between 0 and 32767. User agents should ignore leading zeros.

         The tabbing order defines the order in which elements will receive
         focus when navigated by the user via the keyboard. The tabbing order
         may include elements nested within other elements.

         Elements that may receive focus should be navigated by user agents
         according to the following rules:

         #. Those elements that support the tabindex attribute and assign a
            positive value to it are navigated first. Navigation proceeds from the
            element with the lowest tabindex value to the element with the highest
            value. Values need not be sequential nor must they begin with any
            particular value. Elements that have identical tabindex values should
            be navigated in the order they appear in the character stream.

         #. Those elements that do not support the tabindex attribute or support
            it and assign it a value of "0" are navigated next. These elements are
            navigated in the order they appear in the character stream.

         #. Elements that are disabled do not participate in the tabbing order.

         The actual key sequence that causes tabbing navigation or element
         activation depends on the configuration of the user agent (e.g., the
         "tab" key is used for navigation and the "enter" key is used to
         activate a selected element)

         User agents may also define key sequences to navigate the tabbing
         order in reverse. When the end (or beginning) of the tabbing order is
         reached, user agents may circle back to the beginning (or end).



.. _reference-textline-title:

title
'''''

.. container:: table-row

   Property
         title

   Data type
         string

   Description
         This attribute offers advisory information about the element for which
         it is set. Unlike the TITLE element, which provides information about
         an entire document and may only appear once, the title attribute may
         annotate any number of elements. Please consult an element's
         definition to verify that it supports this attribute.

         Values of the title attribute may be rendered by user agents in a
         variety of ways. For instance, visual browsers frequently display the
         title as a "tool tip" (a short message that appears when the pointing
         device pauses over an object). Audio user agents may speak the title
         information in a similar context.



.. _reference-textline-type:

type
''''

.. container:: table-row

   Property
         type

   Data type
         string

   Description
         Defines the type of form input control to create.

   Default
         text



.. _reference-textline-value:

value
'''''

.. container:: table-row

   Property
         value

   Data type
         string

   Description
         This attribute assigns the initial value to the object.


[tsref:(cObject).FORM.FormObject.TEXTLINE]
