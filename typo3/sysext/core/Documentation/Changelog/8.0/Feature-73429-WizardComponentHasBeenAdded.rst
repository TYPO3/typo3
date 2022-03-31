
.. include:: /Includes.rst.txt

=================================================
Feature: #73429 - Wizard component has been added
=================================================

See :issue:`73429`

Description
===========

A new wizard component has been added. This component may be used for user-guided interactions.
The RequireJS module can be used by including `TYPO3/CMS/Backend/Wizard`.

The wizard supports straight forward actions only, junctions are not possible yet.


Impact
======

The wizard component has the following public methods:

* :code:`addSlide(identifier, title, content, severity, callback)`

* :code:`addFinalProcessingSlide(callback)`

* :code:`set(key, value)`

* :code:`show()`

* :code:`dismiss()`

* :code:`getComponent()`

* :code:`lockNextStep()`

* :code:`unlockNextStep()`


addSlide
~~~~~~~~

Adds a slide to the wizard.

========== =============== ============ ======================================================================================================
Name       DataType        Mandatory    Description
========== =============== ============ ======================================================================================================
identifier string          Yes          The internal identifier of the slide
title      string          Yes          The title of the slide
content    string          Yes          The content of the slide
severity   int                          Represents the severity of a slide. Please see TYPO3.Severity. Default is :code:`TYPO3.Severity.info`.
callback   function                     Callback method run after the slide appeared. The callback receives two parameters:
                                        :code:`$slide`: The current slide as a jQuery object
                                        :code:`settings`: The settings defined via :js:`Wizard.set()`
========== =============== ============ ======================================================================================================

addFinalProcessingSlide
~~~~~~~~~~~~~~~~~~~~~~~

Adds a slide to the wizard containing a spinner. This should always be the latest slide. This method returns a Promise
object due to internal handling. This means you have to add a :js:`done()` callback containing :js:`Wizard.show()` and
:js:`Wizard.getComponent()` please see the example below.

========== =============== ============ ======================================================================================================
Name       DataType        Mandatory    Description
========== =============== ============ ======================================================================================================
callback   function                     Callback method run after the slide appeared. If no callback method is given, the wizard dismisses
                                        without any further action.
========== =============== ============ ======================================================================================================

Example code:

.. code-block:: javascript

        Wizard.addFinalProcessingSlide().done(function() {
            Wizard.show();

            Wizard.getComponent().on('click', '.my-element', function(e) {
                e.preventDefault();
                $(this).doSomething();
            });
        });

set
~~~

Adds values to the internal settings stack usable in other slides.

========== =============== ============ ======================================================================================================
Name       DataType        Mandatory    Description
========== =============== ============ ======================================================================================================
key        string          Yes          The key of the setting
value      string          Yes          The value of the setting
========== =============== ============ ======================================================================================================

Events
~~~~~~

The event `wizard-visible` is fired when the wizard rendering has finished.

Example code:

.. code-block:: javascript

        Wizard.getComponent().on('wizard-visible', function() {
            Wizard.unlockNextButton();
        });


Wizards can be closed by firing the `wizard-dismiss` event.

Example code:

.. code-block:: javascript

        Wizard.getComponent().trigger('wizard-dismiss');


Wizards fire the `wizard-dismissed` event if the wizard is closed. You can integrate your own listener by using :js:`Wizard.getComponent()`.

Example code:

.. code-block:: javascript

        Wizard.getComponent().on('wizard-dismissed', function() {
            // Calculate the answer of life the universe and everything
        });

.. index:: Backend, JavaScript
