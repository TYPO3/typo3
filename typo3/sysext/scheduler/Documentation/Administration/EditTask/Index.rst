:navigation-title: Adding / Editing

..  include:: /Includes.rst.txt
..  _adding-editing-task:

========================
Adding or editing a task
========================

When adding or editing a task, the following form will show up:

..  figure:: ../../Images/AddingATask.png
    :alt: Add task form

    Adding a new scheduled task

Some fields require additional explanations (inline help is
available by moving the mouse over the field labels):

-   A disabled task will be skipped by the command-line script. It may
    still be launched manually, as described above.

-   The class selector is available only when adding a new task. The class
    cannot be changed when editing a task, since there's no sense in that.

..  figure:: ../../Images/TaskConfigurationSelectClass.png
    :alt: Select a class

    Select the class of the scheduled task

..  versionadded:: 13.3
    Similar to editing regular content elements, it is now possible to save
    scheduler tasks being edited via keyboard shortcuts as well.

It is possible to invoke the :kbd:`Ctrl`/:kbd:`Cmd` + :kbd:`s` hotkey to save a
scheduler task, altogether with the hotkey :kbd:`Ctrl`/:kbd:`Cmd` + :kbd:`Shift` + :kbd:`S`
to save and close a scheduler task.

-   A task must have a start date. The end date is not mandatory, though.
    A task without end date will run forever. Dates and times must be
    entered in the server's time zone. The server's time appears at the
    bottom of the form.

-   The frequency needs be entered only for recurring tasks.
    It can be either an integer number of seconds or a cron-like schedule expression.
    Scheduler supports ranges, steps and keywords like ``@weekly``.
    See `en.wikipedia.org <https://en.wikipedia.org/wiki/Cron#CRON_expression>`_ for more information.
    See :php:`\TYPO3\CMS\Scheduler\CronCommand\CronCommand`
    and :php:`\TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand`
    class references in the TYPO3 CMS source code for definitive rules.

-   Parallel executions are denied by default (see "Tasks execution"
    above). They must be allowed explicitly.

-   At the bottom of the form (highlighted area) there may be one or more
    additional fields. Those fields are specific to each task and will
    change when a different class is selected.

If there are some input errors, the form will reload upon submission
and display the relevant error messages. When the input is finished
and correct, the view switches back to the list view and displays a
confirmation message.

..  figure:: ../../Images/InputValidation.png
    :alt: Input validation

    Input validation failed when adding a new scheduled task or editing an existing one


..  figure:: ../../Images/InputValidationOk.png
    :alt: Input validation OK

    Input validation succeeded when adding a new scheduled task or editing an existing one


If an error occurs when validating a cron definition, the
Scheduler's built-in cron parser tries to provide an explanation about
what's wrong.

