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
	import org.flowplayer.flow_internal;	
	
	/**
	 * @author anssi
	 */
	public class ArrayUtil {
		flow_internal static function nonNulls(from:Array):Array {
			var result:Array = new Array();
			for (var i:Number = 0; i < from.length; i++) {
				if (from[i] != null)
					result.push(from[i]);
			}
			return result;
		}
		
		public static function concat(result:Array, addIfAvailable:Array):Array {
			if (addIfAvailable) {
				return result.concat(addIfAvailable);
			}
			return result;
		}
	}
}
