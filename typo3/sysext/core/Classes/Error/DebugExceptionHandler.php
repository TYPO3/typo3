<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Error;

use TYPO3\CMS\Core\Information\Typo3Information;

/**
 * A basic but solid exception handler which catches everything which
 * falls through the other exception handlers and provides useful debugging
 * information.
 */
class DebugExceptionHandler extends AbstractExceptionHandler
{
    protected bool $logExceptionStackTrace = true;

    /**
     * Constructs this exception handler - registers itself as the default exception handler.
     */
    public function __construct()
    {
        $callable = [$this, 'handleException'];
        if (is_callable($callable)) {
            set_exception_handler($callable);
        }
    }

    /**
     * Formats and echoes the exception as XHTML.
     *
     * @param \Throwable $exception The throwable object.
     */
    public function echoExceptionWeb(\Throwable $exception)
    {
        $this->sendStatusHeaders($exception);
        $this->writeLogEntries($exception, self::CONTEXT_WEB);

        $content = $this->getContent($exception);
        $css = $this->getStylesheet();

        echo <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>TYPO3 Exception</title>
        <meta name="robots" content="noindex,nofollow" />
        <style>$css</style>
    </head>
    <body>
        $content
    </body>
</html>
HTML;
    }

    /**
     * Formats and echoes the exception for the command line
     *
     * @param \Throwable $exception The throwable object.
     */
    public function echoExceptionCLI(\Throwable $exception)
    {
        $filePathAndName = $exception->getFile();
        $exceptionCodeNumber = $exception->getCode() > 0 ? '#' . $exception->getCode() . ': ' : '';
        $this->writeLogEntries($exception, self::CONTEXT_CLI);
        echo LF . 'Uncaught TYPO3 Exception ' . $exceptionCodeNumber . $exception->getMessage() . LF;
        echo 'thrown in file ' . $filePathAndName . LF;
        echo 'in line ' . $exception->getLine() . LF . LF;
        die(1);
    }

    /**
     * Generates the HTML for the error output.
     *
     * @param \Throwable $throwable
     * @return string
     */
    protected function getContent(\Throwable $throwable): string
    {
        $content = '';

        // exceptions can be chained
        // for easier debugging, all exceptions are displayed to the developer
        $throwables = $this->getAllThrowables($throwable);
        $count = count($throwables);
        foreach ($throwables as $position => $e) {
            $content .= $this->getSingleThrowableContent($e, $position + 1, $count);
        }

        $exceptionInfo = '';
        if ($throwable->getCode() > 0) {
            $documentationLink = Typo3Information::URL_EXCEPTION . 'debug/' . $throwable->getCode();
            $exceptionInfo = <<<INFO
            <div class="container">
                <div class="callout">
                    <h4 class="callout-title">Get help in the TYPO3 Documentation</h4>
                    <div class="callout-body">
                        <p>
                            If you need help solving this exception, you can have a look at the TYPO3 Documentation.
                            There you can find solutions provided by the TYPO3 community.
                            Once you have found a solution to the problem, help others by contributing to the
                            documentation page.
                        </p>
                        <p>
                            <a href="$documentationLink" target="_blank" rel="noreferrer">Find a solution for this exception in the TYPO3 Documentation.</a>
                        </p>
                    </div>
                </div>
            </div>
INFO;
        }

        $typo3Logo = $this->getTypo3LogoAsSvg();

        return <<<HTML
            <div class="exception-page">
                <div class="exception-summary">
                    <div class="container">
                        <div class="exception-message-wrapper">
                            <div class="exception-illustration hidden-xs-down">$typo3Logo</div>
                            <h1 class="exception-message break-long-words">Whoops, looks like something went wrong.</h1>
                        </div>
                    </div>
                </div>

                $exceptionInfo

                <div class="container">
                    $content
                </div>
            </div>
HTML;
    }

    /**
     * Renders the HTML for a single throwable.
     *
     * @param \Throwable $throwable
     * @param int $index
     * @param int $total
     * @return string
     */
    protected function getSingleThrowableContent(\Throwable $throwable, int $index, int $total): string
    {
        $exceptionTitle = get_class($throwable);
        $exceptionCode = $throwable->getCode() ? '#' . $throwable->getCode() . ' ' : '';
        $exceptionMessage = $this->escapeHtml($throwable->getMessage());

        // The trace does not contain the step where the exception is thrown.
        // To display it as well it is added manually to the trace.
        $trace = $throwable->getTrace();
        array_unshift($trace, [
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'args' => [],
        ]);

        $backtraceCode = $this->getBacktraceCode($trace);

        return <<<HTML
            <div class="trace">
                <div class="trace-head">
                    <h3 class="trace-class">
                        <span class="text-muted">({$index}/{$total})</span>
                        <span class="exception-title">{$exceptionCode}{$exceptionTitle}</span>
                    </h3>
                    <p class="trace-message break-long-words">{$exceptionMessage}</p>
                </div>
                <div class="trace-body">
                    {$backtraceCode}
                </div>
            </div>
HTML;
    }

    /**
     * Generates the stylesheet needed to display the error page.
     *
     * @return string
     */
    protected function getStylesheet(): string
    {
        return <<<STYLESHEET
            html {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
                -ms-overflow-style: scrollbar;
                -webkit-tap-highlight-color: transparent;
            }

            body {
                margin: 0;
            }

            .exception-page {
                background-color: #eaeaea;
                color: #212121;
                font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
                font-weight: 400;
                height: 100vh;
                line-height: 1.5;
                overflow-x: hidden;
                overflow-y: scroll;
                text-align: left;
                top: 0;
            }

            .panel-collapse .exception-page {
                height: 100%;
            }

            .exception-page a {
                color: #ff8700;
                text-decoration: underline;
            }

            .exception-page a:hover {
                text-decoration: none;
            }

            .exception-page abbr[title] {
                border-bottom: none;
                cursor: help;
                text-decoration: none;
            }

            .exception-page code,
            .exception-page kbd,
            .exception-page pre,
            .exception-page samp {
                font-family: SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;
                font-size: 1em;
            }

            .exception-page pre {
                background-color: #ffffff;
                overflow-x: auto;
                border: 1px solid rgba(0,0,0,0.125);
            }

            .exception-page pre span {
                display: block;
                line-height: 1.3em;
            }

            .exception-page pre span:before {
                display: inline-block;
                content: attr(data-line);
                border-right: 1px solid #b9b9b9;
                margin-right: 0.5em;
                padding-right: 0.5em;
                background-color: #f4f4f4;
                width: 4em;
                text-align: right;
                color: #515151;
            }

            .exception-page pre span.highlight {
                background-color: #cce5ff;
            }

            .exception-page .break-long-words {
                -ms-word-break: break-all;
                word-break: break-all;
                word-break: break-word;
                -webkit-hyphens: auto;
                -moz-hyphens: auto;
                hyphens: auto;
            }

            .exception-page .callout {
                padding: 1.5rem;
                background-color: #fff;
                margin-bottom: 2em;
                box-shadow: 0 2px 1px rgba(0,0,0,.15);
                border-left: 3px solid #8c8c8c;
            }

            .exception-page .callout-title {
                margin: 0;
            }

            .exception-page .callout-body p:last-child {
                margin-bottom: 0;
            }

            .exception-page .container {
                max-width: 1140px;
                margin: 0 auto;
                padding: 0 30px;
            }

            .panel-collapse .exception-page .container {
                width: 100%;
            }

            .exception-page .exception-illustration {
                width: 3em;
                height: 3em;
                float: left;
                margin-right: 1rem;
            }

            .exception-page .exception-illustration svg {
                width: 100%;
            }

            .exception-page .exception-illustration svg path {
                fill: #ff8700;
            }

            .exception-page .exception-summary {
                background: #000000;
                color: #fff;
                padding: 1.5rem 0;
                margin-bottom: 2rem;
            }

            .exception-page .exception-summary h1 {
                margin: 0;
            }

            .exception-page .text-muted {
                opacity: 0.5;
            }

            .exception-page .trace {
                background-color: #fff;
                margin-bottom: 2rem;
                box-shadow: 0 2px 1px rgba(0,0,0,.15);
            }

            .exception-page .trace-arguments {
                color: #8c8c8c;
            }

            .exception-page .trace-body {
            }

            .exception-page .trace-call {
                margin-bottom: 1rem;
            }

            .exception-page .trace-class {
                margin: 0;
            }

            .exception-page .trace-file pre {
                margin-top: 1.5rem;
                margin-bottom: 0;
            }

            .exception-page .trace-head {
                color: #721c24;
                background-color: #f8d7da;
                padding: 1.5rem;
            }

            .exception-page .trace-file-path {
                word-break: break-all;
            }

            .exception-page .trace-message {
                margin-bottom: 0;
            }

            .exception-page .trace-step {
                padding: 1.5rem;
                border-bottom: 1px solid #b9b9b9;
            }

            .exception-page .trace-step > *:first-child {
                margin-top: 0;
            }

            .exception-page .trace-step > *:last-child {
                margin-bottom: 0;
            }

            .exception-page .trace-step:nth-child(even)
            {
                background-color: #fafafa;
            }

            .exception-page .trace-step:last-child {
                border-bottom: none;
            }
STYLESHEET;
    }

    /**
     * Renders the backtrace as HTML.
     *
     * @param array $trace
     * @return string
     */
    protected function getBacktraceCode(array $trace): string
    {
        $content = '';

        foreach ($trace as $index => $step) {
            $content .= '<div class="trace-step">';
            $args = $this->flattenArgs($step['args'] ?? []);

            if (isset($step['function'])) {
                $content .= '<div class="trace-call">' . sprintf(
                    'at <span class="trace-class">%s</span><span class="trace-type">%s</span><span class="trace-method">%s</span>(<span class="trace-arguments">%s</span>)',
                    $step['class'] ?? '',
                    $step['type'] ?? '',
                    $step['function'],
                    $this->formatArgs($args)
                ) . '</div>';
            }

            if (isset($step['file']) && isset($step['line'])) {
                $content .= $this->getCodeSnippet($step['file'], $step['line']);
            }

            $content .= '</div>';
        }

        return $content;
    }

    /**
     * Returns a code snippet from the specified file.
     *
     * @param string $filePathAndName Absolute path and file name of the PHP file
     * @param int $lineNumber Line number defining the center of the code snippet
     * @return string The code snippet
     */
    protected function getCodeSnippet(string $filePathAndName, int $lineNumber): string
    {
        $showLinesAround = 4;

        $content = '<div class="trace-file">';
        $content .= '<div class="trace-file-head">' . $this->formatPath($filePathAndName, $lineNumber) . '</div>';

        if (@file_exists($filePathAndName)) {
            $phpFile = @file($filePathAndName);
            if (is_array($phpFile)) {
                $startLine = $lineNumber > $showLinesAround ? $lineNumber - $showLinesAround : 1;
                $phpFileCount = count($phpFile);
                $endLine = $lineNumber < $phpFileCount - $showLinesAround ? $lineNumber + $showLinesAround + 1 : $phpFileCount + 1;
                if ($endLine > $startLine) {
                    $content .= '<div class="trace-file-content">';
                    $content .= '<pre>';

                    for ($line = $startLine; $line < $endLine; $line++) {
                        $codeLine = str_replace("\t", ' ', $phpFile[$line - 1]);
                        $spanClass = '';
                        if ($line === $lineNumber) {
                            $spanClass = 'highlight';
                        }

                        $content .= '<span class="' . $spanClass . '" data-line="' . $line . '">' . $this->escapeHtml($codeLine) . '</span>';
                    }

                    $content .= '</pre>';
                    $content .= '</div>';
                }
            }
        }

        $content .= '</div>';

        return $content;
    }

    /**
     * Formats a path adding a line number.
     *
     * @param string $path The full path of the file.
     * @param int $line The line number.
     * @return string
     */
    protected function formatPath(string $path, int $line): string
    {
        return sprintf(
            '<span class="block trace-file-path">in <strong>%s</strong>%s</span>',
            $this->escapeHtml($path),
            $line > 0 ? ' line ' . $line : ''
        );
    }

    /**
     * Formats the arguments of a method call.
     *
     * @param array $args The flattened args of method/function call
     * @return string
     */
    protected function formatArgs(array $args): string
    {
        $result = [];
        foreach ($args as $key => $item) {
            if ($item[0] === 'object') {
                $formattedValue = sprintf('<em>object</em>(%s)', $item[1]);
            } elseif ($item[0] === 'array') {
                $formattedValue = sprintf('<em>array</em>(%s)', is_array($item[1]) ? $this->formatArgs($item[1]) : $item[1]);
            } elseif ($item[0] === 'null') {
                $formattedValue = '<em>null</em>';
            } elseif ($item[0] === 'boolean') {
                $formattedValue = '<em>' . strtolower(var_export($item[1], true)) . '</em>';
            } elseif ($item[0] === 'resource') {
                $formattedValue = '<em>resource</em>';
            } else {
                $formattedValue = str_replace("\n", '', $this->escapeHtml(var_export($item[1], true)));
            }

            $result[] = \is_int($key) ? $formattedValue : sprintf("'%s' => %s", $this->escapeHtml($key), $formattedValue);
        }

        return implode(', ', $result);
    }

    protected function flattenArgs(array $args, int $level = 0, int &$count = 0): array
    {
        $result = [];
        foreach ($args as $key => $value) {
            if (++$count > 1e4) {
                return ['array', '*SKIPPED over 10000 entries*'];
            }
            if ($value instanceof \__PHP_Incomplete_Class) {
                // is_object() returns false on PHP<=7.1
                $result[$key] = ['incomplete-object', $this->getClassNameFromIncomplete($value)];
            } elseif (is_object($value)) {
                $result[$key] = ['object', get_class($value)];
            } elseif (is_array($value)) {
                if ($level > 10) {
                    $result[$key] = ['array', '*DEEP NESTED ARRAY*'];
                } else {
                    $result[$key] = ['array', $this->flattenArgs($value, $level + 1, $count)];
                }
            } elseif ($value === null) {
                $result[$key] = ['null', null];
            } elseif (is_bool($value)) {
                $result[$key] = ['boolean', $value];
            } elseif (is_int($value)) {
                $result[$key] = ['integer', $value];
            } elseif (is_float($value)) {
                $result[$key] = ['float', $value];
            } elseif (is_resource($value)) {
                $result[$key] = ['resource', get_resource_type($value)];
            } else {
                $result[$key] = ['string', (string)$value];
            }
        }

        return $result;
    }

    protected function getClassNameFromIncomplete(\__PHP_Incomplete_Class $value): string
    {
        $array = new \ArrayObject($value);

        return $array['__PHP_Incomplete_Class_Name'];
    }

    protected function escapeHtml(string $str): string
    {
        return htmlspecialchars($str, ENT_COMPAT | ENT_SUBSTITUTE);
    }

    protected function getTypo3LogoAsSvg(): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M11.1 10.3c-.2 0-.3.1-.5.1C9 10.4 6.8 5 6.8 3.2c0-.7.2-.9.4-1.1-2 .2-4.2.9-4.9 1.8-.2.2-.3.6-.3 1 0 2.8 3 9.2 5.1 9.2 1 0 2.6-1.6 4-3.8m-1-8.4c1.9 0 3.9.3 3.9 1.4 0 2.2-1.4 4.9-2.1 4.9C10.6 8.3 9 4.7 9 2.9c0-.8.3-1 1.1-1"></path></svg>
SVG;
    }

    protected function getAllThrowables(\Throwable $throwable): array
    {
        $all = [$throwable];

        while ($throwable = $throwable->getPrevious()) {
            $all[] = $throwable;
        }

        return $all;
    }
}
