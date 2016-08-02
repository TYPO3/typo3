.. include:: ../../../Includes.txt


.. _change-layout-specific-view:

=====================================
Change the layout for a specific view
=====================================

.. contents::
    :local:
    :depth: 1


.. _change-layout-specific-view-available-views:

Available views
===============

There are 3 views available:

form:
  This view displays the form with its form fields which can be filled by
  the user and submitted.

confirmation:
  If activated, this view shows a confirmation page which has to be
  confirmed by the user.

postProcessor:
  The mail postProcessor has its own view for rendering the mail which is
  sent to the receiver.

It is not recommended to change the layout of a FORM object for all views.
For example when customizing the TEXTLINE object the integrator will get
strange results based on the following example:

.. code-block:: typoscript

  tt_content.mailform.20 {
    layout {
      textline (
        <div class="form-group">
          <div class="col-sm-3 control-label">
            <label />
          </div>
          <div class="col-sm-5">
            <input class="form-control" />
          </div>
        </div>
      )
    }
  }

The setup shown above changes the appearance of all TEXTLINE objects for all
views. That is, the user will get a confirmation page and a mail with
broken/ senseless input fields instead of the user data.

In order to only change the TEXTLINE object specific to all of the 3 views,
the following code could be applied.

.. code-block:: typoscript

  tt_content.mailform.20 {
    # customize form view
    form {
      layout {
        textline (
          <div class="form-group">
            <div class="col-sm-3 control-label">
              <label />
            </div>
            <div class="col-sm-5">
              <input class="form-control" />
            </div>
          </div>
        )
      }
    }

    # customize confirmation view
    confirmationView {
      layout {
        textline (
          <div class="form-group">
            <div class="col-sm-3">
              <strong><label /></strong>
            </div>
            <div class="col-sm-5">
              <inputvalue />
            </div>
          </div>
        )
      }
    }

    # customize postProcessor/ mail
    postProcessor {
      layout {
        textline (
          <td colspan="2">
            <div class="textline"><inputvalue /></div>
          </td>
        )
      }
    }
  }


.. _change-layout-specific-view-properties:

Properties and defaults
=======================

If the integrator does not define any :ts:`.layout` setting the default
layout defined in the PHP classes will be used.

The following list shows all available elements within all the different
views including their corresponding default layouts.

.. contents::
    :local:
    :depth: 1


.. _reference-layout-form:

form
^^^^

:aspect:`Property:`
    form

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form

:aspect:`Description:`
    Layout of the FORM object/ outer wrap.

    The <containerwrap /> tag will be substituted by the outer container
    wrap and includes all child elements.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <form>
        <containerWrap />
      </form>


.. _reference-layout-confirmation:

confirmation
^^^^^^^^^^^^

:aspect:`Property:`
    confirmation

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - confirmation

:aspect:`Description:`
    Layout of the outer wrap.

    The <containerwrap /> tag will be substituted by the outer container
    wrap and includes all child elements.

:aspect:`Default:`
    Default layout **confirmation view**:

    .. code-block:: html

      <containerWrap />


.. _reference-layout-html:

html
^^^^

:aspect:`Property:`
    html

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - postProcessor

:aspect:`Description:`
    Layout of the outer wrap.

    The <containerwrap /> tag will be substituted by the outer container
    wrap and includes all child elements.

:aspect:`Default:`
    Default layout **postProcessor view**:

    .. code-block:: html

      <html>
        <head>
          <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        </head>
        <body>
          <table cellspacing="0">
            <containerWrap />
          </table>
        </body>
      </html>


.. _reference-layout-containerwrap:

containerWrap
^^^^^^^^^^^^^

:aspect:`Property:`
    containerWrap

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Inner wrap for container objects.

    The <elements /> tag will be substituted with all the child elements,
    including their element wraps.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <ol>
        <elements />
      </ol>

    Default layout **confirmation view**:

    .. code-block:: html

      <ol>
        <elements />
      </ol>

    Default layout **postProcessor view**:

    .. code-block:: html

      <tbody>
        <elements />
      </tbody>


.. _reference-layout-elementwrap:

elementWrap
^^^^^^^^^^^

:aspect:`Property:`
    elementWrap

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Outer wrap for regular objects.

    The <element /> tag will be substituted with the child element.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <li>
        <element />
      </li>

    Default layout **confirmation view**:

    .. code-block:: html

      <li>
        <element />
      </li>

    Default layout **postProcessor view**:

    .. code-block:: html

      <tr>
        <element />
      </tr>


.. _reference-layout-label:

label
^^^^^

:aspect:`Property:`
    label

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the labels.

    The <labelvalue /> tag will be substituted with the label text.

    If available, the <mandatory /> tag will be substituted with the
    validation rule message, styled by its own layout.

    If available, the <error /> tag will be substituted with the error
    message from the validation rule when the submitted value is not valid.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label>
        <labelvalue />
        <mandatory />
        <error />
      </label>

    Default layout **confirmation view**:

    .. code-block:: html

      <label>
        <labelvalue />
      </label>

    Default layout **postProcessor view**:

    .. code-block:: html

      <em>
        <labelvalue />
      </em>


.. _reference-layout-mandatory:

mandatory
^^^^^^^^^

:aspect:`Property:`
    mandatory

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form

:aspect:`Description:`
    Layout for the validation rule message to describe the rule.

    The <mandatoryvalue /> tag will be substituted with the validation rule
    message.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <em>
        <mandatoryvalue />
      </em>


.. _reference-layout-error:

error
^^^^^

:aspect:`Property:`
    error

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form

:aspect:`Description:`
    Layout for the validation rule error message when the submitted data
    does not validate.

    The <errorvalue /> tag will be substituted with the validation rule
    error message.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <strong>
        <errorvalue />
      </strong>


.. _reference-layout-legend:

legend
^^^^^^

:aspect:`Property:`
    legend

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the legend.

    The <legendvalue /> tag will be substituted with the legend text.

    If available, the <mandatory /> tag will be substituted with the
    validation rule message, styled by its own layout.

    If available, the <error /> tag will be substituted with the error
    message from the validation rule when the submitted value is not valid.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <legend>
        <legendvalue />
        <mandatory />
        <error />
      </legend>

    Default layout **confirmation view**:

    .. code-block:: html

      <legend>
        <legendvalue />
      </legend>


    Default layout **postProcessor view**:

    .. code-block:: html

      <thead>
        <tr>
          <th colspan="2" align="left">
            <legendvalue />
          </th>
        </tr>
      </thead>


.. _reference-layout-button:

button
^^^^^^

:aspect:`Property:`
    button

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form

:aspect:`Description:`
    Layout for the BUTTON object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label />
      <input />


.. _reference-layout-checkbox:

checkbox
^^^^^^^^

:aspect:`Property:`
    checkbox

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the CHECKBOX object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label />
      <input />

    Default layout **confirmation view**:

    .. code-block:: html

      <label />
      <inputvalue />

    Default layout **postProcessor view**:

    .. code-block:: html

      <td style="width: 200px;">
        <label />
      </td>
      <td>
        <inputvalue />
      </td>


.. _reference-layout-checkboxgroup:

checkboxgroup
^^^^^^^^^^^^^

:aspect:`Property:`
    checkboxgroup

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the CHECKBOXGROUP object.

    The <containerwrap /> tag will be substituted by the outer container
    wrap and includes all child elements.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <fieldset>
        <legend />
        <containerWrap />
      </fieldset>

    Default layout **confirmation view**:

    .. code-block:: html

      <fieldset>
        <legend />
        <containerWrap />
      </fieldset>

    Default layout **postProcessor view**:

    .. code-block:: html

      <td colspan="2">
        <table cellspacing="0" style="padding-left: 20px; margin-bottom: 20px;">
          <legend />
          <containerWrap />
        </table>
      </td>


.. _reference-layout-fieldset:

fieldset
^^^^^^^^

:aspect:`Property:`
    fieldset

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the FIELDSET object.

    The <containerwrap /> tag will be substituted by the outer container
    wrap and includes all child elements.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <fieldset>
        <legend />
        <containerWrap />
      </fieldset>

    Default layout **confirmation view**:

    .. code-block:: html

      <fieldset>
        <legend />
        <containerWrap />
      </fieldset>

    Default layout **postProcessor view**:

    .. code-block:: html

      <td colspan="2">
        <table cellspacing="0" style="padding-left: 20px; margin-bottom: 20px;">
          <legend />
          <containerWrap />
        </table>
      </td>


.. _reference-layout-fileupload:

fileupload
^^^^^^^^^^

:aspect:`Property:`
    fileupload

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the FILEUPLOAD object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label />
      <input />

    Default layout **confirmation view**:

    .. code-block:: html

      <label />
      <inputvalue />

    Default layout **postProcessor view**:

    .. code-block:: html

      <td style="width: 200px;">
        <label />
      </td>
      <td>
        <inputvalue />
      </td>


.. _reference-layout-hidden:

hidden
^^^^^^

:aspect:`Property:`
    hidden

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - postProcessor

:aspect:`Description:`
    Layout for the HIDDEN object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <input />

    Default layout **postProcessor view**:

    .. code-block:: html

      <td style="width: 200px;">
        <em>
          <label />
        </em>
      </td>
      <td>
        <inputvalue />
      </td>


.. _reference-layout-imagebutton:

imagebutton
^^^^^^^^^^^

:aspect:`Property:`
    imagebutton

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form

:aspect:`Description:`
    Layout for the IMAGEBUTTON object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label />
      <input />


.. _reference-layout-optgroup:

optgroup
^^^^^^^^

:aspect:`Property:`
    optgroup

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the OPTGROUP object.

    The <elements /> tag will be substituted with all the child elements,
    which actually can only be OPTION objects.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <optgroup>
        <elements />
      </optgroup>

    Default layout **confirmation view**:

    .. code-block:: html

      <elements />

    Default layout **postProcessor view**:

    .. code-block:: html

      <elements />


.. _reference-layout-option:

option
^^^^^^

:aspect:`Property:`
    option

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the OPTION object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <option />

    Default layout **confirmation view**:

    .. code-block:: html

      <inputvalue />

    Default layout **postProcessor view**:

    .. code-block:: html

      <div>
        <inputvalue />
      </div>


.. _reference-layout-password:

password
^^^^^^^^

:aspect:`Property:`
    password

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form

:aspect:`Description:`
    Layout for the PASSWORD object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label />
      <input />


.. _reference-layout-radio:

radio
^^^^^

:aspect:`Property:`
    radio

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the RADIO object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label />
      <input />

    Default layout **confirmation view**:

    .. code-block:: html

      <label />
      <inputvalue />

    Default layout **postProcessor view**:

    .. code-block:: html

      <td style="width: 200px;">
        <label />
      </td>
      <td>
        <inputvalue />
      </td>


.. _reference-layout-radiogroup:

radiogroup
^^^^^^^^^^

:aspect:`Property:`
    radiogroup

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the RADIOGROUP object.

    The <containerwrap /> tag will be substituted by the outer container
    wrap and includes all child elements.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <fieldset>
        <legend />
        <containerWrap />
      </fieldset>


    Default layout **confirmation view**:

    .. code-block:: html

      <fieldset>
        <legend />
        <containerWrap />
      </fieldset>

    Default layout **postProcessor view**:

    .. code-block:: html

      <td colspan="2">
        <table cellspacing="0" style="padding-left: 20px; margin-bottom: 20px;">
          <legend />
          <containerWrap />
        </table>
      </td>


.. _reference-layout-reset:

reset
^^^^^

:aspect:`Property:`
    reset

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form

:aspect:`Description:`
    Layout for the RESET object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label />
      <input />


.. _reference-layout-select:

select
^^^^^^

:aspect:`Property:`
    select

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the SELECT object.

    The <elements /> tag will be substituted with all the child elements,
    which only can be OPTGROUP or OPTION objects.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label />
      <select>
         <elements />
      </select>

    Default layout **confirmation view**:

    .. code-block:: html

      <label />
      <ol>
         <elements />
      </ol>

    Default layout **postProcessor view**:

    .. code-block:: html

      <td style="width: 200px;">
        <label />
      </td>
      <td>
        <elements />
      </td>


.. _reference-layout-submit:

submit
^^^^^^

:aspect:`Property:`
    submit

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form

:aspect:`Description:`
    Layout for the SUBMIT object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label />
      <input />


.. _reference-layout-textarea:

textarea
^^^^^^^^

:aspect:`Property:`
    textarea

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the TEXTAREA object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label />
      <textarea />


    Default layout **confirmation view**:

    .. code-block:: html

      <label />
      <inputvalue />

    Default layout **postProcessor view**:

    .. code-block:: html

      <td style="width: 200px;" valign="top">
          <label />
      </td>
      <td>
          <inputvalue />
      </td>


.. _reference-layout-textblock:

textblock
^^^^^^^^^

:aspect:`Property:`
    textblock

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form

:aspect:`Description:`
    Layout for the TEXTBLOCK object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <textblock />


.. _reference-layout-textline:

textline
^^^^^^^^

:aspect:`Property:`
    textline

:aspect:`Data type:`
    string

:aspect:`Available in views:`
    - form
    - confirmation
    - postProcessor

:aspect:`Description:`
    Layout for the TEXTLINE object.

:aspect:`Default:`
    Default layout **form view**:

    .. code-block:: html

      <label />
      <input />

    Default layout **confirmation view**:

    .. code-block:: html

      <label />
      <inputvalue />

    Default layout **postProcessor view**:

    .. code-block:: html

      <td style="width: 200px;">
        <label />
      </td>
      <td>
        <inputvalue />
      </td>


.. _change-layout-specific-view-example:

Example showing all .layout properties and defaults
===================================================

The code snippets below shows all available settings across all views
including their default layout.

.. code-block:: typoscript

  tt_content.mailform.20 {
    # ###
    # form view
    # ####

    form {
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

        label (
          <label>
            <labelvalue />
            <mandatory />
            <error />
          </label>
        )

        mandatory (
          <em>
            <mandatoryvalue />
          </em>
        )

        error (
          <strong>
            <errorvalue />
          </strong>
        )

        legend (
          <legend>
            <legendvalue />
            <mandatory />
            <error />
          </legend>
        )

        button (
          <label />
          <input />
        )

        checkbox (
          <label />
          <input />
        )

        checkboxgroup (
          <fieldset>
            <legend />
            <containerWrap />
          </fieldset>
        )
        fieldset (
            <fieldset>
                <legend />
                <containerWrap />
            </fieldset>
        )

        fileupload (
          <label />
          <input />
        )

        hidden (
          <input />
        )

        imagebutton (
          <label />
          <input />
        )

        optgroup (
          <optgroup>
            <elements />
          </optgroup>
        )

        option (
          <option />
        )

        password (
          <label />
          <input />
        )

        radio (
          <label />
          <input />
        )

        radiogroup (
          <fieldset>
            <legend />
            <containerWrap />
          </fieldset>
        )

        reset (
          <label />
          <input />
        )

        select (
          <label />
          <select>
            <elements />
          </select>
        )

        submit (
          <label />
          <input />
        )

        textarea (
          <label />
          <textarea />
        )

        textblock (
          <textblock />
        )

        textline (
          <label />
          <input />
        )
      }
    }

    # ###
    # confirmation view
    # ###

    confirmationView {
      layout {
        confirmation (
          <containerWrap />
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

        label (
          <label>
            <labelvalue />
          </label>
        )

        legend (
          <legend>
            <legendvalue />
          </legend>
        )

        checkbox (
          <label />
          <inputvalue />
        )

        checkboxgroup (
          <fieldset>
            <legend />
            <containerWrap />
          </fieldset>
        )

        fieldset (
          <fieldset>
            <legend />
            <containerWrap />
          </fieldset>
        )

        fileupload (
          <label />
          <inputvalue />
        )

        optgroup (
          <elements />
        )

        option (
          <inputvalue />
        )

        radio (
          <label />
          <inputvalue />
        )

        radiogroup (
          <fieldset>
            <legend />
            <containerWrap />
          </fieldset>
        )

        select (
          <label />
          <ol>
            <elements />
          </ol>
        )

        textarea (
          <label />
          <inputvalue />
        )

        textline (
          <label />
          <inputvalue />
        )
      }
    }

    # ###
    # postProcesso view
    # ###

    postProcessor {
      layout {
        html (
          <html>
            <head>
              <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            </head>
            <body>
              <table cellspacing="0">
                <containerWrap />
              </table>
            </body>
          </html>
        )

        containerWrap (
          <tbody>
            <elements />
          </tbody>
        )

        elementWrap (
          <tr>
            <element />
          </tr>
        )

        label (
          <em>
            <labelvalue />
          </em>
        )

        legend (
          <thead>
            <tr>
              <th colspan="2" align="left">
                <legendvalue />
              </th>
            </tr>
          </thead>
        )

        checkbox (
          <td style="width: 200px;">
            <label />
          </td>
          <td>
            <inputvalue />
          </td>
        )

        checkboxgroup (
          <td colspan="2">
            <table cellspacing="0" style="padding-left: 20px; margin-bottom: 20px;">
              <legend />
              <containerWrap />
            </table>
          </td>
        )

        fieldset (
          <td colspan="2">
            <table cellspacing="0" style="padding-left: 20px; margin-bottom: 20px;">
              <legend />
              <containerWrap />
            </table>
          </td>
        )

        fileupload (
          <td style="width: 200px;">
            <label />
          </td>
          <td>
            <inputvalue />
          </td>
        )

        hidden (
          <td style="width: 200px;">
            <em>
              <label />
            </em>
          </td>
          <td>
            <inputvalue />
          </td>
        )

        optgroup (
          <elements />
        )

        option (
          <div>
            <inputvalue />
          </div>
        )

        radio (
          <td style="width: 200px;">
            <label />
          </td>
          <td>
            <inputvalue />
          </td>
        )

        radiogroup (
          <td colspan="2">
            <table cellspacing="0" style="padding-left: 20px; margin-bottom: 20px;">
              <legend />
              <containerWrap />
            </table>
          </td>
        )

        select (
          <td style="width: 200px;">
            <label />
          </td>
          <td>
            <elements />
          </td>
        )

        textarea (
          <td style="width: 200px;" valign="top">
            <label />
          </td>
          <td>
            <inputvalue />
          </td>
        )

        textline (
          <td style="width: 200px;">
            <label />
          </td>
          <td>
            <inputvalue />
          </td>
        )
      }
    }
  }

