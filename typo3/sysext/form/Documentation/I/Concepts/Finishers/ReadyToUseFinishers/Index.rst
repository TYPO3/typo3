..  include:: /Includes.rst.txt
..  _concepts-finishers-ready-to-use:

======================
Ready-to-use finishers
======================

he TYPO3 Form Framework provides several built-in finishers that can be
used out of the box. These handle common post-submission tasks such as
sending emails, showing confirmation messages, or saving data.

In addition, third-party extensions may provide further finishers, which
can be found in the `TYPO3 Extension Repository (TER) <https://extensions.typo3.org/>`_.

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: `Closure finisher <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-closurefinisher>`_

        Executes a custom PHP closure after a successful submission—use
        for ad-hoc logic without creating a full class.

    ..  card:: `Confirmation finisher <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-confirmationfinisher>`_

        Renders a confirmation/thank-you message (or view) once the form
        is submitted.

    ..  card:: `DeleteUploads finishers <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-deleteuploadsfinisher>`_

        Removes files uploaded during the submission—handy after
        emailing them if you don’t want files kept on the server.

    ..  card:: `DeleteUploads finishers <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-deleteuploadsfinisher>`_

        Sends an email based on the submitted data; supports Fluid
        templates and placeholders for field values.

    ..  card:: :doc:`Flash message finisher <FlashMessageFinisher/Index>`

        Shows a flash message to the user after submit (e.g., success or
        info notice).

    ..  card:: `Redirect finisher <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-redirectfinisher>`_

        Redirects to another page or route after submit; place it last
        since it stops subsequent finishers.

    ..  card:: `SaveToDatabase finisher <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-savetodatabasefinisher>`_

        Persists submitted form values to a database table according to
        your mapping/configuration.

..  toctree::
    :hidden:
    :glob:

    */Index
