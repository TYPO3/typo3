..  include:: /Includes.rst.txt
..  _concepts-finishers-redirectfinisher:

=================
Redirect finisher
=================

The "Redirect finisher" is a simple finisher that redirects to another page.
Additional link parameters can be added to the URL.

..  important::

    Finishers are executed in the order defined in your form definition.
    This finisher stops the execution of all subsequent finishers in order to perform
    the redirect. Therefore, this finisher should always be the last finisher to be
    executed. Finishers after this one will never be executed.

..  _concepts-finishers-redirectfinisher-last:

Example: Load the redirect finisher last
========================================

..  literalinclude:: _codesnippets/_example-redirect.yaml
    :caption: public/fileadmin/forms/my_form_with_multiple_finishers.yaml
