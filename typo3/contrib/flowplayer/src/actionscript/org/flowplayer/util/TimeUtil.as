/*
 *    Copyright (c) 2008-2011 Flowplayer Oy *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Flowplayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Flowplayer.  If not, see <http://www.gnu.org/licenses/>.
 */
package org.flowplayer.util {

    public class TimeUtil {
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
            if(str.substr(-1) == 'h') {
                return Number(str.substr(0, str.length - 1)) * 3600;
            }
            if(arr.length > 1) {
                sec = Number(arr[arr.length - 1]);
                sec += Number(arr[arr.length - 2]) * 60;
                if(arr.length == 3) {
                    sec += Number(arr[arr.length - 3]) * 3600;
                }
                return sec;
            }
            return Number(str);
        }
    }
}