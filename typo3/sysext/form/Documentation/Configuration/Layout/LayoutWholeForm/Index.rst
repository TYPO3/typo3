.. include:: ../../../Includes.txt


.. _change-layout-of-whole-form:

===================================
Change the layout of the whole form
===================================

.. attention::

    It is not recommended to change the layout globally for the whole form.
    Unfortunately, using view specific layout settings did not work for a
    long time and is now widely used by integrators.

    There are several reasons for not to use global layout settings:

    - Some objects cannot be changed globally.
    - Changing some objects will cause problems which lead to failures in
      the processing. The code will die with PHP errors.
    - Quite often it does not make sense to do these changes globally.

    Instead change the layout for a :ref:`specific view <change-layout-specific-view>`!

Apart from the above mentioned problems one could change the layout globally
using the following TypoScript setup. Using :ts:`tt_content.mailform.20`
registers the chances for all forms of the below the page tree. If one wants
to change the layout only for a specific form, a TypoScript library could be
build as shown :ref:`here <reference-form-example>`.

.. code-block:: typoscript

  tt_content.mailform.20 {
    layout {
      # changing the layout of the form object globally
      form (
        <form class="form-class">
          <containerWrap />
        </form>
      )
    }
  }

As one can see, an (X)HTML kind of markup is used. Actually it is XML, with
some extra tags like the :ts:`containerWrap`.

