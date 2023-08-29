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
"use strict";"undefined"!=typeof $&&$((function(t){t("input[data-t3-form-datepicker]").each((function(){t(this).datepicker({dateFormat:t(this).data("format")}).on("keydown",(function(e){8!==e.keyCode&&46!==e.keyCode||(e.preventDefault(),t(this).datepicker("setDate",""))}))}))}));