import Tablesort from "tablesort";
export default (new function() {
  const module = { exports: {} }, exports = module.exports, define = null;
/*!
 * tablesort v5.2.1 (2021-10-30)
 * http://tristen.ca/tablesort/demo/
 * Copyright (c) 2021 ; Licensed MIT
*/
Tablesort.extend("dotsep",function(a){return/^(\d+\.)+\d+$/.test(a)},function(a,b){a=a.split("."),b=b.split(".");for(var c,d,e=0,f=a.length;e<f;e++)if(c=parseInt(a[e],10),d=parseInt(b[e],10),c!==d){if(c>d)return-1;if(c<d)return 1}return 0});
  this.__default_export = module.exports;
}).__default_export;