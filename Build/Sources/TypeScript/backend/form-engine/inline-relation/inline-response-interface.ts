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

import {AjaxDispatcherResponse} from '@typo3/backend/form-engine/inline-relation/ajax-dispatcher';

export interface InlineResponseInterface extends AjaxDispatcherResponse{
  data: string;
  inlineData: InlineData;
  scriptCall: Array<string>;
  stylesheetFiles: Array<string>;
  compilerInput?: CompilerInput,
}

interface InlineData {
  config: { [key: string]: Object };
  map: { [key: string]: Array<string> };
  nested: { [key: string]: Array<Array<string>> };
}

interface CompilerInput {
  uid: string;
  childChildUid: string;
  parentConfig: { [key: string]: any };
  delete?: Array<string>;
  localize?: Array<LocalizeItem>;
}

interface LocalizeItem {
  uid: string;
  selectedValue: string;
  remove?: number;
}
