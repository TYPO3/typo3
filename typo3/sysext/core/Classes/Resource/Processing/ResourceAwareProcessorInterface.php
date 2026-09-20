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

namespace TYPO3\CMS\Core\Resource\Processing;

/**
 * Marker interface for processors that can handle a task whose source file is not a
 * FAL File - most commonly a system resource shipped with a package.
 *
 * TaskInterface::getSourceFile()/getTargetFile() are typed widely enough to allow this,
 * but a processor's canProcessTask()/processTask() historically assumed a File/ProcessedFile
 * and may dereference File-specific methods. ProcessorRegistry only ever offers a non-FAL
 * task to a processor that has explicitly opted in by implementing this interface, so an
 * existing (including third-party) ProcessorInterface implementation that has not been
 * reviewed for this is never handed a task it cannot actually process.
 *
 * Like any interface, it is inherited: a subclass of a processor implementing it is
 * offered non-FAL tasks as well, and has to decline them in canProcessTask() itself if
 * it can only handle a FAL File.
 */
interface ResourceAwareProcessorInterface extends ProcessorInterface {}
