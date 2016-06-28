.. include:: ../../Includes.txt


.. _reference-layout:

======
Layout
======

.. attention::

    The form wizard (available in the TYPO3 backend) does not support the
    complex layout mechanism described in this chapter. As soon as the
    integrator has applied custom layout settings, the form wizard should
    not be used anymore. When opening the customized form inside the form
    wizard and hitting the "Save" button, all custom layout settings will be
    lost.

Using layout allows the integrator to change the default visual appearance
of the FORM objects.

The FORM consists of FORM objects, which have their own layout each. The
layout of these objects can be changed for the whole form, for a specific
view or just for a particular object.

By default, the overall markup is based on ordered lists with list elements
in it, to have a proper layout framework which is also accessible for people
with disabilities.

Some objects are considered being container objects, as they have child
objects. These objects are FORM, FIELDSET, CHECKBOXGROUP and RADIOGROUP. To
have a proper markup for these objects, nested ordered lists are used.

**Example**

.. code-block:: html

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

It could be stated that SELECT and OPTGROUP elements are container objects
as well, and actually this is correct. They also contain child objects. But
these objects are not allowed to use the above mentioned markup.

There are 3 ways to modify the layout:

.. toctree::
    :maxdepth: 5
    :titlesonly:
    :glob:

    LayoutWholeForm/Index
    LayoutViewSpecific/Index
    LayoutObjectSpecific/Index

