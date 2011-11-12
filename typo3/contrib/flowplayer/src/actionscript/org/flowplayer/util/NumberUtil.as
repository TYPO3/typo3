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

	/**
	 * @author anssi
	 */
	public class NumberUtil {
		
		public static function decodeNonNumbers(number:Number, toValue:Number = 0):Number {
			if (isNaN(number)) return toValue;
			return number;
		} 
		
		public static function decodePercentage(percentageStr:String):Number {
			var result:Number = evaluate("pct", percentageStr);
			if (! isNaN(result)) return result;
			return evaluate("%", percentageStr);
		}
		
		public static function decodePixels(pixelsStr:String):Number {
			if (pixelsStr.indexOf("px") < 0) {
				pixelsStr += "px";
			}
			var result:Number = evaluate("px", pixelsStr);
			if (! isNaN(result)) return result;

			result = decodePercentage(pixelsStr);
			if (! isNaN(result)) {
				// was a percentage value!
				return NaN;
			}
			return pixelsStr.substr(0) as Number;
		}

		private static function evaluate(sequence:String, valStr:String):Number {
			if (valStr.indexOf(sequence) <= 0) return NaN;
			return Number(valStr.substring(0, valStr.indexOf(sequence))); 
		}
		
	}
}
