..  include:: /Includes.rst.txt

..  _feature-106839-1749197278:

=========================================================================
Feature: #106839 - Introduce shell auto-completion for the `typo3` script
=========================================================================

See :issue:`106839`

Description
===========

The new `bin/typo3 completion` command is introduced to the :shell:`typo3` CLI
dispatcher script, allowing to register auto-completion for supported shells.
This makes using the CLI dispatcher more fun by having the ability to simply
use the `TAB` key (tabulator key) to trigger suggestions (multiple options), or
complete the command or option in case there is only one.

The command `completion` is provided by :php:`symfony/console` and is not a custom
implementation, thus allowing to be enhanced by constant improvements of the Symfony
community, and getting support based on an even broader user base.

Supported shells:

..  code-block:: bash

    # bin/typo3 completion shell
    Detected shell "shell", which is not supported by Symfony shell completion
    (supported shells: "bash", "fish", "zsh").

The command provides two basic installation modes, `static` and `dynamic` which
is explained by the command itself:

..  code-block:: bash

    $ bin/typo3 completion --help
    Description:
      Dump the shell completion script

    Usage:
      completion [options] [--] [<shell>]

    Arguments:
      shell                 The shell type (e.g. "bash"), the value of the "$SHELL" env var will be used if this is not given

    Options:
          --debug           Tail the completion debug log
      -h, --help            Display help for the given command. When no command is given display help for the list command
          --silent          Do not output any message
      -q, --quiet           Only errors are displayed. All other output is suppressed
      -V, --version         Display this application version
          --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
      -n, --no-interaction  Do not ask any interactive question
      -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

    Help:
      The completion command dumps the shell completion script required
      to use shell autocompletion (currently, bash, fish, zsh completion are supported).

      Static installation
      -------------------

      Dump the script to a global completion file and restart your shell:

          bin/typo3 completion bash | sudo tee /etc/bash_completion.d/typo3

      Or dump the script to a local file and source it:

          bin/typo3 completion bash > completion.sh

          # source the file whenever you use the project
          source completion.sh

          # or add this line at the end of your "~/.bashrc" file:
          source /path/to/completion.sh

      Dynamic installation
      --------------------

      Add this to the end of your shell configuration file (e.g. "~/.bashrc"):

          eval "$(/var/www/work/t3c/core-main/core-main/bin/typo3 completion bash)"

Usually, this completion will be set within the project's specific environment (for example
in DDEV or Docker containers), because commands can vary if multiple projects are involved.

Impact
======

Improve the experience using the `typo3` CLI command dispatcher script, without
impacting current usages. Also, this allows further improvements on existing commands or
new commands by declaring completion behaviour for command options.

Example
-------

..  code-block:: php
    :caption: GreetCommand.php

    <?php

    // ...
    use Symfony\Component\Console\Completion\CompletionInput;
    use Symfony\Component\Console\Completion\CompletionSuggestions;

    class GreetCommand extends Command
    {
        // ...
        protected function configure(): void
        {
            $this
                ->addArgument(
                    'names',
                    InputArgument::IS_ARRAY,
                    'Who do you want to greet (separate multiple names with a space)?',
                    null,
                    function (CompletionInput $input): array {
                        // the value the user already typed, e.g. when typing "myext:greet Fa"
                        // before pressing Tab, this will contain "Fa"
                        $currentValue = $input->getCompletionValue();

                        // get the list of username names from somewhere (e.g. the database)
                        // you may use $currentValue to filter down the names
                        $availableUsernames = ...;

                        // then return the possible suggested usernames as values
                        return $availableUsernames;
                    }
                )
            ;
        }
    }

For more details, see `Symfony Console Adding Argument Option Value Completion`_
and also for testing purpose see `Testing the Completion script`_.

..  _Symfony Console Adding Argument Option Value Completion: https://symfony.com/doc/7.2/console/input.html#adding-argument-option-value-completion
..  _Testing the Completion script: https://symfony.com/doc/7.2/console/input.html#testing-the-completion-script

..  index:: CLI, ext:core
