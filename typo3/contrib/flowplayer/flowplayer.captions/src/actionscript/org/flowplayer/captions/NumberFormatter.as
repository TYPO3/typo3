/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * Time formatter thanks to the JW Player Project
 *
 * By: Daniel Rossi, <electroteque@gmail.com>
 * Copyright (c) 2009 Electroteque Multimedia
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php 
 */

package org.flowplayer.captions {

    public class NumberFormatter {

        public static function seconds(str:String, timeMultiplier:Number = 1000):Number {
            return Math.round(toSeconds(str) * timeMultiplier / 100) * 100;
        }

        private static function toSeconds(str:String):Number {
            str = str.replace(",", ".");
            var arr:Array = str.split(':');
            var sec:Number = 0;
            if (str.substr(-1) == 's') {
                return Number(str.substr(0, str.length - 1));
            }
            if (str.substr(-1) == 'm') {
                return Number(str.substr(0, str.length - 1)) * 60;
            }
            if (str.substr(-1) == 'h') {
                return Number(str.substr(0, str.length - 1)) * 3600;
            }
            if (arr.length > 1) {
                sec = Number(arr[arr.length - 1]);
                sec += Number(arr[arr.length - 2]) * 60;
                if (arr.length == 3) {
                    sec += Number(arr[arr.length - 3]) * 3600;
                }
                return sec;
            }
            return Number(str);
        }
    }
}