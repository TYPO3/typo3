/**
 * @license
 * Copyright 2021 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */
function*oo(o,f){if(void 0!==o){let i=0;for(const t of o)yield f(t,i++)}}export{oo as map};
