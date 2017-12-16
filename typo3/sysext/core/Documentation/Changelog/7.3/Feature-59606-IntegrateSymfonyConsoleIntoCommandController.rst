
.. include:: ../../Includes.txt

==================================================================
Feature: #59606 - Integrate Symfony/Console into CommandController
==================================================================

See :issue:`59606`

Description
===========

The CommandController now makes use of Symfony/Console internally and
provides various methods directly from the CommandController's `output` member:

* TableHelper

	* outputTable($rows, $headers = NULL)

* DialogHelper

	* select($question, $choices, $default = NULL, $multiSelect = false, $attempts = FALSE)
	* ask($question, $default = NULL, array $autocomplete = array())
	* askConfirmation($question, $default = TRUE)
	* askHiddenResponse($question, $fallback = TRUE)
	* askAndValidate($question, $validator, $attempts = FALSE, $default = NULL, array $autocomplete = NULL)
	* askHiddenResponseAndValidate($question, $validator, $attempts = FALSE, $fallback = TRUE)

* ProgressHelper

	* progressStart($max = NULL)
	* progressSet($current)
	* progressAdvance($step = 1)
	* progressFinish()

Here's an example showing of some of those functions:

.. code-block:: php

	namespace Acme\Demo\Command;

	use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

	/**
	 * My command
	 */
	class MyCommandController extends CommandController {

		/**
		 * @return string
		 */
		public function myCommand() {
			// render a table
			$this->output->outputTable(array(
				array('Bob', 34, 'm'),
				array('Sally', 21, 'f'),
				array('Blake', 56, 'm')
			),
			array('Name', 'Age', 'Gender'));

			// select
			$colors = array('red', 'blue', 'yellow');
			$selectedColorIndex = $this->output->select('Please select one color', $colors, 'red');
			$this->outputLine('You choose the color %s.', array($colors[$selectedColorIndex]));

			// ask
			$name = $this->output->ask('What is your name?' . PHP_EOL, 'Bob', array('Bob', 'Sally', 'Blake'));
			$this->outputLine('Hello %s.', array($name));

			// prompt
			$likesDogs = $this->output->askConfirmation('Do you like dogs?');
			if ($likesDogs) {
				$this->outputLine('You do like dogs!');
			}

			// progress
			$this->output->progressStart(600);
			for ($i = 0; $i < 300; $i ++) {
				$this->output->progressAdvance();
				usleep(5000);
			}
			$this->output->progressFinish();

		}
	}


Impact
======

This change does not alter the public API so it is not breaking
in the strict sense. But it introduces a new behavior:
Previously all output was collected in the `Cli\Response` and only rendered to the console at the end of a CLI request.
Now all methods producing output (including `output()` and `outputLine()`) render the result directly to the console.
If you use `$this->response` directly or let the command method return a string, the rendering is still deferred until
the end of the CLI request.
