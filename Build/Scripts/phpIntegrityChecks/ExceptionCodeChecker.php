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

namespace TYPO3\CMS\PhpIntegrityChecks;

use PhpParser\Node;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\MathUtility;

final class ExceptionCodeChecker extends AbstractPhpIntegrityChecker
{
    private array $usedCodes = [];
    private array $duplicatedCodes = [];
    private array $undefinedCodes = [];
    private array $malformedCodes = [];

    public function enterNode(Node $node): void
    {
        if (!($node instanceof Node\Expr\Throw_ && $node->expr instanceof Node\Expr\New_)) {
            return;
        }
        $nodeClass = $node->expr?->class ?? null;
        $exceptionCodeArgument = null;
        // this is situation 'named argument provided'
        foreach ($node->expr->args as $argument) {
            if ($argument->name instanceof Node\Identifier && $argument->name->name === 'code') {
                $exceptionCodeArgument = $argument;
                break;
            }
        }
        if ($exceptionCodeArgument === null) {
            // for a PHP Exception, the code parameter is second argument
            $exceptionCodeParameterPositionIndex = 1;
            if (!empty($nodeClass->getAttribute('constructor', []))) {
                // this is a TYPO3 Core Exception class, information was added before
                foreach ($nodeClass->getAttribute('constructor', []) as $key => $parameter) {
                    if ($parameter->name === 'code') {
                        $exceptionCodeParameterPositionIndex = $key;
                        break;
                    }
                }
                if ($exceptionCodeParameterPositionIndex === -1) {
                    throw new \RuntimeException('Exception class ' . $nodeClass->name . ' has no detectable code parameter', 1720251381);
                }
            }
            $exceptionCodeArgument = $node->expr->args[$exceptionCodeParameterPositionIndex] ?? null;
        }
        $position = $this->getRelativeFileNameFromRepositoryRoot() . ' ' . $node->getLine();
        if (!$exceptionCodeArgument instanceof Node\Arg) {
            $this->undefinedCodes['undefined'][] = $position;
            $this->messages['undefinedCodes'] = ['see $this->undefinedCodes'];
            return;
        }
        try {
            $exceptionCode = $exceptionCodeArgument->value->value;
        } catch (\Exception) {
            if ($exceptionCodeArgument->value instanceof Node\Expr\MethodCall && $exceptionCodeArgument->value->name->name === 'getCode') {
                // some magic happens reusing a previously caught exception. Just leave it alone.
                return;
            }
            $this->malformedCodes['undefined'][] = $position;
            $this->messages['malformedCodes'] = ['see $this->malformedCodes'];
            return;
        }
        if (!MathUtility::canBeInterpretedAsInteger($exceptionCode) || strlen((string)$exceptionCode) !== 10) {
            $this->malformedCodes[$exceptionCode][] = $position;
            $this->messages['malformedCodes'] = ['see $this->malformedCodes'];
        }
        if (!array_key_exists($exceptionCode, $this->usedCodes)) {
            $this->usedCodes[$exceptionCode] = $position;
        } elseif (!array_key_exists($exceptionCode, $this->duplicatedCodes)) {
            $this->duplicatedCodes[$exceptionCode] = [
                $this->usedCodes[$exceptionCode],
                $position,
            ];
            $this->messages['duplicatedCodes'] = ['see $this->duplicatedCodes'];
        } else {
            $this->duplicatedCodes[$exceptionCode][] = $position;
            $this->messages['duplicatedCodes'] = ['see $this->duplicatedCodes'];
        }
    }

    public function outputResult(SymfonyStyle $io, array $issueCollection): void
    {
        $nothingFound = true;
        $io->title('Exception Code checker result');
        if ($this->duplicatedCodes !== []) {
            $nothingFound = false;
            $message = 'Duplicated Exception Codes detected. Make sure each is unique (e.g. use timestamps):';
            $this->drawResultTable($io, $message, $this->duplicatedCodes);
        }
        if ($this->undefinedCodes !== []) {
            $nothingFound = false;
            $message = 'Undefined Exception Codes detected. Make sure each exception throw has one (e.g. use timestamps):';
            $this->drawResultTable($io, $message, $this->undefinedCodes);
        }
        if ($this->malformedCodes !== []) {
            $nothingFound = false;
            $message = 'Malformed Exception Codes detected. Make sure each exception throw uses a 10 digit int (e.g. use timestamps):';
            $this->drawResultTable($io, $message, $this->malformedCodes);
        }
        if ($nothingFound) {
            $io->success('Exception Code integrity is in good shape.');
        }
    }

    private function drawResultTable(SymfonyStyle $io, string $message, array $input): void
    {
        $io->error($message);
        $table = new Table($io);
        $table->setHeaders([
            'Position',
            'Exception code',
        ]);
        foreach ($input as $code => $positions) {
            foreach ($positions as $position) {
                $table->addRow([$position, $code]);
            }
        }
        $table->render();
    }
}
