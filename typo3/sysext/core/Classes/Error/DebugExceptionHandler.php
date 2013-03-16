<?php
namespace TYPO3\CMS\Core\Error;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A basic but solid exception handler which catches everything which
 * falls through the other exception handlers and provides useful debugging
 * information.
 *
 * This file is a backport from FLOW3
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class DebugExceptionHandler extends \TYPO3\CMS\Core\Error\AbstractExceptionHandler {

	/**
	 * Constructs this exception handler - registers itself as the default exception handler.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		set_exception_handler(array($this, 'handleException'));
	}

	/**
	 * Formats and echoes the exception as XHTML.
	 *
	 * @param Exception $exception The exception object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function echoExceptionWeb(\Exception $exception) {
		$this->sendStatusHeaders($exception);
		$filePathAndName = $exception->getFile();
		$exceptionCodeNumber = $exception->getCode() > 0 ? '#' . $exception->getCode() . ': ' : '';
		$moreInformationLink = $exceptionCodeNumber != '' ? '(<a href="' . TYPO3_URL_EXCEPTION . 'debug/' . $exception->getCode() . '" target="_blank">More information</a>)' : '';
		$backtraceCode = $this->getBacktraceCode($exception->getTrace());
		$this->writeLogEntries($exception, self::CONTEXT_WEB);
		// Set the XML prologue
		$xmlPrologue = '<?xml version="1.0" encoding="utf-8"?>';
		// Set the doctype declaration
		$docType = '<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.1//EN"
     "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
		// Get the browser info
		$browserInfo = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT'));
		// Put the XML prologue before or after the doctype declaration according to browser
		if ($browserInfo['browser'] === 'msie' && $browserInfo['version'] < 7) {
			$headerStart = $docType . LF . $xmlPrologue;
		} else {
			$headerStart = $xmlPrologue . LF . $docType;
		}
		echo $headerStart . '
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
				<head>
					<title>TYPO3 Exception</title>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
					<style type="text/css">
						.ExceptionProperty {
							color: #101010;
						}
						pre {
							margin: 0;
							font-size: 11px;
							color: #515151;
							background-color: #D0D0D0;
							padding-left: 30px;
						}
					</style>
				</head>
				<body>
					<div style="
							position: absolute;
							left: 10px;
							background-color: #B9B9B9;
							outline: 1px solid #515151;
							color: #515151;
							font-family: Arial, Helvetica, sans-serif;
							font-size: 12px;
							margin: 10px;
							padding: 0;
						">
						<div style="width: 100%; background-color: #515151; color: white; padding: 2px; margin: 0 0 6px 0;">Uncaught TYPO3 Exception</div>
						<div style="width: 100%; padding: 2px; margin: 0 0 6px 0;">
							<strong style="color: #BE0027;">' . $exceptionCodeNumber . htmlspecialchars($exception->getMessage()) . '</strong> ' . $moreInformationLink . '<br />
							<br />
							<span class="ExceptionProperty">' . get_class($exception) . '</span> thrown in file<br />
							<span class="ExceptionProperty">' . htmlspecialchars($filePathAndName) . '</span> in line
							<span class="ExceptionProperty">' . $exception->getLine() . '</span>.<br />
							<br />
							' . $backtraceCode . '
						</div>
					</div>
				</body>
			</html>
		';
	}

	/**
	 * Formats and echoes the exception for the command line
	 *
	 * @param Exception $exception The exception object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function echoExceptionCLI(\Exception $exception) {
		$filePathAndName = $exception->getFile();
		$exceptionCodeNumber = $exception->getCode() > 0 ? '#' . $exception->getCode() . ': ' : '';
		$this->writeLogEntries($exception, self::CONTEXT_CLI);
		echo '
Uncaught TYPO3 Exception ' . $exceptionCodeNumber . $exception->getMessage() . LF;
		echo 'thrown in file ' . $filePathAndName . LF;
		echo 'in line ' . $exception->getLine() . '

';
	}

	/**
	 * Renders some backtrace
	 *
	 * @param array $trace The trace
	 * @return string Backtrace information
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getBacktraceCode(array $trace) {
		$backtraceCode = '';
		if (count($trace)) {
			foreach ($trace as $index => $step) {
				$class = isset($step['class']) ? $step['class'] . '<span style="color:white;">::</span>' : '';
				$arguments = '';
				if (isset($step['args']) && is_array($step['args'])) {
					foreach ($step['args'] as $argument) {
						$arguments .= strlen($arguments) === 0 ? '' : '<span style="color:white;">,</span> ';
						if (is_object($argument)) {
							$arguments .= '<span style="color:#FF8700;"><em>' . get_class($argument) . '</em></span>';
						} elseif (is_string($argument)) {
							$preparedArgument = strlen($argument) < 100 ? $argument : substr($argument, 0, 50) . '#tripleDot#' . substr($argument, -50);
							$preparedArgument = htmlspecialchars($preparedArgument);
							$preparedArgument = str_replace('#tripleDot#', '<span style="color:white;">&hellip;</span>', $preparedArgument);
							$preparedArgument = str_replace(LF, '<span style="color:white;">&crarr;</span>', $preparedArgument);
							$arguments .= '"<span style="color:#FF8700;" title="' . htmlspecialchars($argument) . '">' . $preparedArgument . '</span>"';
						} elseif (is_numeric($argument)) {
							$arguments .= '<span style="color:#FF8700;">' . (string) $argument . '</span>';
						} else {
							$arguments .= '<span style="color:#FF8700;"><em>' . gettype($argument) . '</em></span>';
						}
					}
				}
				$backtraceCode .= '<pre style="color:#69A550; background-color: #414141; padding: 4px 2px 4px 2px;">';
				$backtraceCode .= '<span style="color:white;">' . (count($trace) - $index) . '</span> ' . $class . $step['function'] . '<span style="color:white;">(' . $arguments . ')</span>';
				$backtraceCode .= '</pre>';
				if (isset($step['file'])) {
					$backtraceCode .= $this->getCodeSnippet($step['file'], $step['line']) . '<br />';
				}
			}
		}
		return $backtraceCode;
	}

	/**
	 * Returns a code snippet from the specified file.
	 *
	 * @param string $filePathAndName Absolute path and file name of the PHP file
	 * @param integer $lineNumber Line number defining the center of the code snippet
	 * @return string The code snippet
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getCodeSnippet($filePathAndName, $lineNumber) {
		$codeSnippet = '<br />';
		if (@file_exists($filePathAndName)) {
			$phpFile = @file($filePathAndName);
			if (is_array($phpFile)) {
				$startLine = $lineNumber > 2 ? $lineNumber - 2 : 1;
				$endLine = $lineNumber < count($phpFile) - 2 ? $lineNumber + 3 : count($phpFile) + 1;
				if ($endLine > $startLine) {
					$codeSnippet = '<br /><span style="font-size:10px;">' . $filePathAndName . ':</span><br /><pre>';
					for ($line = $startLine; $line < $endLine; $line++) {
						$codeLine = str_replace(TAB, ' ', $phpFile[$line - 1]);
						if ($line === $lineNumber) {
							$codeSnippet .= '</pre><pre style="background-color: #F1F1F1; color: black;">';
						}
						$codeSnippet .= sprintf('%05d', $line) . ': ' . $codeLine;
						if ($line === $lineNumber) {
							$codeSnippet .= '</pre><pre>';
						}
					}
					$codeSnippet .= '</pre>';
				}
			}
		}
		return $codeSnippet;
	}

}


?>