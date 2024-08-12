..  include:: /Includes.rst.txt

..  _quickstartIntegrators:

===========================
Quick Start for Integrators
===========================

You are an integrator, your admin or you installed the form extension
and you want to get started quickly? Just follow these steps!

..  rst-class:: bignums-xxl

#.  Include the site set

    ..  versionadded:: 13.3
        EXT:form offers a site set that can be included as described here.
        :ref:`quickstartIntegrators-typoscript-includes` are still possible
        for compability reasons but not recommended anymore.

    Include the site set "Form Framework" via the :ref:`site set in the site
    configuration <t3coreapi:site-sets>` or the custom
    :ref:`site package's site set <t3sitepackage:site_set>`.

    ..  figure:: /Images/SiteSet.png

        Add the site set "Form Framework"

#.  Create a new form

    Go to the ``Forms`` module, and create a new form there. With the help of
    the form editor you can build appealing forms easily.

#.  Move the form definition

    If you wish, you can :ref:`move the form definition to a dedicated
    extension<concepts-form-file-storages>`.

#.  Provide a translation

    You can also provide a :ref:`translation<concepts-frontendrendering-translation>`
    of your form, if needed. This is done in an .xlf file which has to be
    registered in your YAML configuration.

#.  Insert your form in a page

    The final step is inserting the form in the desired page(s).

    #.  Open the page module in the backend.
    #.  Select the desired page.
    #.  Create a new content element of type "Form". You can find this one
        under the tab "Form Elements".
    #.  Under the tab "Plugin", choose the desired form.
    #.  If needed, you can select "Override finisher settings" under the
        "Plugin" tab. Save the content element.
    #.  Repeat steps 2 to 5 until the form is inserted in every page requiring
        it.

You should now be able to view your form on the frontend. Enjoy!

..  _quickstartIntegrators-typoscript-includes:

Legacy TypoScript includes
==========================

..  versionchanged:: 13.3
    It is recommended to include the TypoScript via site set. The legacy way
    of using TypoScript includes, in the past also called "TypoScript sets"
    is still possible for compatibility reasons but not recommended anymore.


Open the ``TypoScript`` module in the backend and edit your root
TypoScript record. Under the tab "Includes", ensure that "Fluid Content
Elements" (`fluid_styled_content`) and "Form" (`form`) are among the selected
items. Save the record.

Then continue with the steps above.