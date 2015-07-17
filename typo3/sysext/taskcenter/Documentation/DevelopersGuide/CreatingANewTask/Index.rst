.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _create-new-task:

Creating a new task
^^^^^^^^^^^^^^^^^^^


.. _create-task-class:

Creating the task class
"""""""""""""""""""""""

This is the heart of a task. It is the code that actually does what
the task is supposed to do. A task is represented by a PHP class that
implements the interface :code:`\TYPO3\CMS\Taskcenter\TaskInterface`:

::

   namespace Foo\Bar\Task;
   class DoSomething implements \TYPO3\CMS\Taskcenter\TaskInterface {
     public function getTask() {
               ...
     }
     public function getOverview() {
               ...
    }
   }

The 2 mentioned method  **must** be implemented!

getTask()
  The function :code:`getTask()` is expected to perform the task logic and
  returns the content of the task.

getOverview()
  The function :code:`getOverview()` creates an optional menu of items or
  description which is rendered in the menu of tasks.


.. _autoloading:

Autoloading
"""""""""""

The Taskcenter expects all task classes to be available with the TYPO3
autoloader. They thus must be declared in the
:file:`ext\_autoload.php` file of the extension that provides these
classes. Again the :file:`ext\_autoload.php` file of sys\_action can
be used as an example.


.. _declare-task-class:

Declaring the task class
""""""""""""""""""""""""

As a last step, the task class must be declared so the Taskcenter
knows of its existence. The declaration must be placed in the
:file:`ext_tables.php` file of the extension that provides the
task. Let's look at the declaration of sys\_action as an example:

::

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']['sys_action']['tx_sysaction_task'] = array(
		'title' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action',
		'description' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_csh_sysaction.xlf:.description',
		'icon' => 'EXT:sys_action/Resources/Public/Images/x-sys_action.png'
	);

The registration is made in the array
:code:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']`. A key is
then used that corresponds to the task class (in bold above). Then
come 3 parameters:

- **title** : the name of the task. May use localized labels.

- **description** : a text describing the task. It is displayed in the
  information screen of the BE module. May use localized labels.

- **icon** : Path to an icon for the task.

