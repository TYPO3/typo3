.. include:: ../../../Includes.txt


.. _reference-object-attributes:

=================
Object Attributes
=================

.. contents::
    :local:
    :depth: 1


.. _reference-objects-attributes-accept:

accept
======

:aspect:`Property:`
    accept

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute specifies a comma-separated list of content types that a
    server processing this form will handle correctly.

    User agents may use this information to filter out non-conforming files
    when prompting a user to select files to be sent to the server (cf. the
    INPUT element when type="file").

    RFC2045: For a complete list, see http://www.iana.org/assignments/media-types/


.. _reference-objects-attributes-accept-charset:

accept-charset
==============

:aspect:`Property:`
    accept-charset

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute specifies the list of character encodings for input data
    that is accepted by the server processing this form.

    The value is a space- and/or comma-delimited list of charset values.

    The client must interpret this list as an exclusive-or list. I.e., the
    server is able to accept any single character encoding per entity
    received.

    The default value for this attribute is the reserved string "UNKNOWN".
    User agents may interpret this value as the character encoding that was
    used to transmit the document containing this FORM element.

    RFC2045: For a complete list, see http://www.iana.org/assignments/character-sets/


.. _reference-objects-attributes-accesskey:

accesskey
=========

:aspect:`Property:`
    accesskey

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute assigns an access key to an element.

    An access key is a single character from the document character set.

    **Note**: Authors should consider the input method of the expected
    reader when specifying an accesskey.

    Pressing an access key assigned to an element gives focus to the
    element.

    The action that occurs when an element receives focus depends on the
    element. For example, when a user activates a link defined by the
    element, the user agent generally follows the link. When a user
    activates a radio button, the user agent changes the value of the radio
    button. When the user activates a text field, it allows input, etc.


.. _reference-objects-attributes-action:

action
======

:aspect:`Property:`
    action

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute specifies a form processing agent.

    In normal circumstances the action attribute will be filled
    automatically, because the form must call the same URI where the form
    resides.

    Besides specifying a page uid it is also possible to set an anchor. See
    the examples below.

    .. code-block:: typoscript

      action = #anchor
      action = 4#anchor


.. _reference-objects-attributes-alt:

alt
===

:aspect:`Property:`
    alt

:aspect:`Data type:`
    string

:aspect:`Description:`
    For user agents that cannot display images, forms, or applets, this
    attribute specifies alternative text. The language of this text is
    specified by the lang attribute.


.. _reference-objects-attributes-checked:

checked
=======

:aspect:`Property:`
    checked

:aspect:`Data type:`
    boolean/ checked

:aspect:`Description:`
    When the type attribute has the value "radio" or "checkbox", this
    boolean attribute specifies that the button is activated.

    User agents must ignore this attribute for other control types.

    **Examples:**

    .. code-block:: typoscript

      checked = 1
      checked = 0
      checked = checked


.. _reference-objects-attributes-class:

class
=====

:aspect:`Property:`
    class

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute assigns a class name or set of class names to an element.

    Any number of elements may be assigned the same class name or names.

    Multiple class names must be separated by white space characters.


.. _reference-objects-attributes-cols:

cols
====

:aspect:`Property:`
    cols

:aspect:`Data type:`
    integer

:aspect:`Description:`
    This attribute specifies the visible width.

    Users should be able to enter longer lines than this, so user agents
    should provide some means to scroll through the contents of the control
    when the contents extend beyond the visible area. User agents may wrap
    visible text lines to keep long lines visible without the need for
    scrolling.

:aspect:`Default:`
    40


.. _reference-objects-attributes-content:

content
=======

:aspect:`Property:`
    content

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute contains the content of a FORM object.


.. _reference-objects-attributes-data:

data
====

:aspect:`Property:`
    data

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute contains the content of a FORM object.


.. _reference-objects-attributes-dir:

dir
===

:aspect:`Property:`
    dir

:aspect:`Data type:`
    ltr/ rtl

:aspect:`Description:`
    This attribute specifies the base direction of directionally neutral
    text (i.e., text that does not have inherent directionality as defined
    in [UNICODE]) in an element's content and attribute values.

    It also specifies the directionality of tables. Possible values:

    - LTR: Left-to-right text or table.

    - RTL: Right-to-left text or table.

    In addition to specifying the language of a document with the lang
    attribute, authors may need to specify the base directionality
    (left-to-right or right-to-left) of portions of a document's text, of a
    table structure, etc. This is done with the dir attribute.


.. _reference-objects-attributes-disabled:

disabled
========

:aspect:`Property:`
    disabled

:aspect:`Data type:`
    boolean/ disabled

:aspect:`Description:`
    When set for a form control, this boolean attribute disables the control
    for user input.

    When set, the disabled attribute has the following effects on an
    element:

    - Disabled controls do not receive focus.

    - Disabled controls are skipped in tabbing navigation.

    - Disabled controls cannot be successful.

    This attribute is inherited but local declarations override the
    inherited value.

    How disabled elements are rendered depends on the user agent. For
    example, some user agents "gray out" disabled menu items, button labels,
    etc.

    **Examples:**

    .. code-block:: typoscript

      disabled = 1
      disabled = 0
      disabled = disabled


.. _reference-objects-attributes-enctype:

enctype
=======

:aspect:`Property:`
    enctype

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute specifies the content type used to submit the form to the
    server (when the value of method is "post"). The default value for this
    attribute is "application/x-www-form-urlencoded".

    The value "multipart/form-data" should be used in combination with the
    INPUT element, type="file".

:aspect:`Default:`
    application/x-www-form-urlencoded


.. _reference-objects-attributes-filters:

filters
=======

:aspect:`Property:`
    filters

:aspect:`Data type:`
    [array of numbers]

    ->filters

:aspect:`Description:`
    Add filters to the FORM object.

    This accepts multiple filters for one FORM object, but you have to add
    these filters one by one. The submitted data for this particular object
    will be filtered by the assigned filters in the given order.

    The filtered data will be shown to the visitor when there are errors in
    the form or on a confirmation page. Otherwise the filtered data will be
    send by mail to the receiver.

    **Example:**

    .. code-block:: typoscript

      filters {
        1 = alphabetic
        1 (
          allowWhiteSpace = 1
        )
        2 = titlecase
      }

    **Submitted data:** john doe3

    **Filtered:** John Doe

    **Note:**: By default, all submitted data will be filtered by a Cross
    Site Scripting (XSS) filter to prevent security issues.

:aspect:`Default:`
    .. code-block:: typoscript

      filters {
        0 = removexss
      }


.. _reference-objects-attributes-headingSize:

headingSize
===========

:aspect:`Property:`
    headingSize

:aspect:`Data type:`
    h1, h2, h3, h4, h5

:aspect:`Description:`
    This attributes allows to wrap the content of a FORM object with a
    headline tag.

:aspect:`Default:`
    h1


.. _reference-objects-attributes-id:

id
==

:aspect:`Property:`
    id

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute assigns an id to an element.

    This id must be unique in a document.

    If an id has been assigned to the object and a value has been entered
    for the label, the "for" attribute will inherit the id.

    **Example for FORM object BUTTON:**

    .. code-block:: html

      <label for="click">Push this button</label>
      <input type="button" id="click" value="Click me" />


.. _reference-objects-attributes-label:

label
=====

:aspect:`Property:`
    label

:aspect:`Data type:`
    string/ cObject

:aspect:`Description:`
    The value of the label of a FORM object.

    By default the value of the label is a TEXT cObject, but you can use
    other cObjects as well. When no cObject type is used it assumes you want
    to use TEXT. In this case you can assign the value directly to the label
    property or indirectly to the value property of the label.

    For more information about cObjects, take a look in the document TSREF.

    **Example:**

    .. code-block:: typoscript

      label = TEXT
      label {
        value = First name
      }

    **Example:**

    .. code-block:: typoscript

      label = First name

    **Example:**

    .. code-block:: typoscript

      label.value = First name


.. _reference-objects-attributes-lang:

lang
====

:aspect:`Property:`
    lang

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute specifies the base language of an element's attribute
    values and text content. The default value of this attribute is unknown.

    Briefly, language codes consist of a primary code and a possibly empty
    series of subcodes:

    - language-code = primary-code ( "-" subcode )\*

    Here are some sample language codes:

    - *en*: English

    - *en-US*: the U.S. version of English

    - *en-cockney*: the Cockney version of English

    - *i-navajo*: the Navajo language spoken by some Native Americans

    - *x-klingon*: The primary tag "x" indicates an experimental language tag


.. _reference-objects-attributes-layout:

layout
======

:aspect:`Property:`
    layout

:aspect:`Data type:`
    string

:aspect:`Description:`
    See general information for  :ref:`reference-layout`.


.. _reference-objects-attributes-legend:

legend
======

:aspect:`Property:`
    legend

:aspect:`Data type:`
    string/ cObject

:aspect:`Description:`
    The value of the legend of a FORM object.

    By default the value of the legend is a TEXT cObject, but you can use
    other cObjects as well. When no cObject type is used it assumes you want
    to use TEXT. In this case you can assign the value directly to the
    legend property or indirectly to the value property of the legend.

    For more information about cObjects, take a look in the document TSREF.

    **Example:**

    .. code-block:: typoscript

      legend = TEXT
      legend {
        value = Personal information
      }


    **Example:**

    .. code-block:: typoscript

      legend = Personal information


    **Example:**

    .. code-block:: typoscript

      legend.value = Personal information


.. _reference-objects-attributes-maxlength:

maxlength
=========

:aspect:`Property:`
    maxlength

:aspect:`Data type:`
    integer

:aspect:`Description:`
    This attribute specifies the maximum number of characters the user may
    enter. This number may exceed the specified size, in which case the user
    agent should offer a scrolling mechanism. The default value for this
    attribute is an unlimited number.


.. _reference-objects-attributes-method:

method
======

:aspect:`Property:`
    method

:aspect:`Data type:`
    post/ get

:aspect:`Description:`
    Specifies which HTTP method will be used to submit form data.

    Only form data submitted with the entered or default method will be
    processed.

:aspect:`Default:`
    get


.. _reference-objects-attributes-multiple:

multiple
========

:aspect:`Property:`
    multiple

:aspect:`Data type:`
    boolean/ multiple

:aspect:`Description:`
    If set, this boolean attribute allows multiple selections.

    If not set, the SELECT element only permits single selections.

    **Examples:**

    .. code-block:: typoscript

      multiple = 1
      multiple = 0
      multiple = multiple


.. _reference-objects-attributes-name:

name
====

:aspect:`Property:`
    name

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute names the element so that submitted data can be
    identified by processing the form server side.

    If no name has been given, it will get assigned an internal counter
    together with the prefix, like:

    .. code-block:: html

      <input type="button" name="tx_form[21]" value="click" />
      <input type="checkbox" name="tx_form[22]" value="click" />


.. _reference-objects-attributes-postProcessor:

postProcessor
=============

:aspect:`Property:`
    postProcessor

:aspect:`Data type:`
    [array of numbers]

:aspect:`Description:`
    Add postprocessors to the FORM.

    This accepts multiple postprocessors for one FORM object, but they have
    to be added one by one.

    **Example** :

    .. code-block:: typoscript

      postProcessor {
        1 = mail
        1 {
          recipientEmail = bar@foo.org
          senderEmail = foo@bar.com
        }
      }


.. _reference-objects-attributes-prefix:

prefix
======

:aspect:`Property:`
    prefix

:aspect:`Data type:`
    string

:aspect:`Description:`
    The prefix of the values in the name attributes of the FORM objects.

    <input name=" **prefix** [first\_name]" value="" />

:aspect:`Default:`
    tx\_form


.. _reference-objects-attributes-readonly:

readonly
========

:aspect:`Property:`
    readonly

:aspect:`Data type:`
    boolean/ readonly

:aspect:`Description:`
    When set for a form control, this boolean attribute prohibits changes to
    the control.

    The readonly attribute specifies whether the control may be modified by
    the user.

    When set, the readonly attribute has the following effects on an
    element:

    - Read-only elements receive focus but cannot be modified by the user.

    - Read-only elements are included in tabbing navigation.

    - Read-only elements may be successful.

    How read-only elements are rendered depends on the user agent.

    **Examples:**

    .. code-block:: html

      readonly = 1
      readonly = 0
      readonly = disabled

    **Note**: The only way to modify dynamically the value of the readonly
    attribute is through a script.


.. _reference-objects-attributes-rows:

rows
====

:aspect:`Property:`
    rows

:aspect:`Data type:`
    integer

:aspect:`Description:`
    This attribute specifies the number of visible text lines.

    Users should be able to enter more lines than this, so user agents
    should provide some means to scroll through the contents of the control
    when the contents extend beyond the visible area.

:aspect:`Default:`
    5


.. _reference-objects-attributes-rules:

rules
=====

:aspect:`Property:`
    rules

:aspect:`Data type:`
    [array of numbers]

:aspect:`Description:`
    Add validation rules to the FORM.

    This accepts multiple validation rules for one FORM object, but the
    rules have to be added one by one. It is also possible to add validation
    rules for different FORM objects.

    **Example:**

    .. code-block:: typoscript

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

    Validation rules are a powerful tool to add validation to the form.
    Please take a look at the rules section in this manual.


.. _reference-objects-attributes-selected:

selected
========

:aspect:`Property:`
    selected

:aspect:`Data type:`
    boolean/ selected

:aspect:`Description:`
    When set, this boolean attribute specifies that a option is pre-
    selected.

    **Examples:**

    .. code-block:: typoscript

      selected = 1
      selected = 0
      selected = selected


.. _reference-objects-attributes-size:

size
====

:aspect:`Property:`
    size

:aspect:`Data type:`
    integer

:aspect:`Description:`
    This attribute tells the user agent the initial width of the control.
    The size has to be entered as integer without any measuring unit.


.. _reference-objects-attributes-src:

src
===

:aspect:`Property:`
    src

:aspect:`Data type:`
    imgResource

:aspect:`Description:`
    This attribute specifies the location of the image to be used to
    decorate the graphical submit button. GIFBUILDER objects are not
    allowed.


.. _reference-objects-attributes-style:

style
=====

:aspect:`Property:`
    style

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute specifies CSS style information for the current element.


.. _reference-objects-attributes-tabindex:

tabindex
========

:aspect:`Property:`
    tabindex

:aspect:`Data type:`
    integer

:aspect:`Description:`
    This attribute specifies the position of the current element in the
    tabbing order for the current document. This value must be a number
    between 0 and 32767. User agents should ignore leading zeros.

    The tabbing order defines the order in which elements will receive focus
    when navigated by the user via the keyboard. The tabbing order may
    include elements nested within other elements.

    Elements that may receive focus should be navigated by user agents
    according to the following rules:

    #. Those elements that support the tabindex attribute and assign a
       positive value to it are navigated first. Navigation proceeds from
       the element with the lowest tabindex value to the element with the
       highest value. Values neither need to be sequential nor must begin
       with any particular value. Elements that have identical tabindex
       values should be navigated in the order they appear in the character
       stream.

    #. Those elements that do not support the tabindex attribute or support
       it and assign it a value of "0" are navigated next. These elements
       are navigated in the order they appear in the character stream.

    #. Elements that are disabled do not participate in the tabbing order.

    The actual key sequence that causes tabbing navigation or element
    activation depends on the configuration of the user agent (e.g., the
    "tab" key is used for navigation and the "enter" key is used to activate
    a selected element),

    User agents may also define key sequences to navigate the tabbing order
    in reverse. When the end (or beginning) of the tabbing order is reached,
    user agents may circle back to the beginning (or end).


.. _reference-objects-attributes-title:

title
=====

:aspect:`Property:`
    title

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute offers advisory information about the element for which
    it is set. Unlike the TITLE element, which provides information about an
    entire document and may only appear once, the title attribute may
    annotate any number of elements. Please consult an element's definition
    to verify that it supports this attribute.

    Values of the title attribute may be rendered by user agents in a
    variety of ways. For instance, visual browsers frequently display the
    title as a "tool tip" (a short message that appears when the pointing
    device pauses over an object). Audio user agents may speak the title
    information in a similar context.


.. _reference-objects-attributes-type:

type
====

:aspect:`Property:`
    type

:aspect:`Data type:`
    string

:aspect:`Description:`
    Defines the type of form input control to create.


.. _reference-objects-attributes-value:

value
=====

:aspect:`Property:`
    value

:aspect:`Data type:`
    string

:aspect:`Description:`
    This attribute assigns the initial value to the object.

