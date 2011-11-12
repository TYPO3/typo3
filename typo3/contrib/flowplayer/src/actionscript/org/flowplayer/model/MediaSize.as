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

package org.flowplayer.model {
	import flash.utils.Dictionary;		

	/**
	 * @author api
	 */
	public class MediaSize {
		
		public static const FITTED_PRESERVING_ASPECT_RATIO:MediaSize = new MediaSize("fit");
		public static const HALF_FROM_ORIGINAL:MediaSize = new MediaSize("half");
		public static const ORIGINAL:MediaSize = new MediaSize("orig");
        public static const FILLED_TO_AVAILABLE_SPACE:MediaSize = new MediaSize("scale");
        public static const CROP_TO_AVAILABLE_SPACE:MediaSize = new MediaSize("crop");

        public static var ALL_VALUES:Dictionary = new Dictionary();
        {
            ALL_VALUES[FITTED_PRESERVING_ASPECT_RATIO._value] = FITTED_PRESERVING_ASPECT_RATIO;
            ALL_VALUES[HALF_FROM_ORIGINAL._value] = HALF_FROM_ORIGINAL;
            ALL_VALUES[ORIGINAL._value] = ORIGINAL;
            ALL_VALUES[FILLED_TO_AVAILABLE_SPACE._value] = FILLED_TO_AVAILABLE_SPACE;
            ALL_VALUES[CROP_TO_AVAILABLE_SPACE._value] = CROP_TO_AVAILABLE_SPACE;
		}

		private static var enumCreated:Boolean;
		{ enumCreated = true; 
		}

		private var _value:String;

		public function MediaSize(value:String) {
			if (enumCreated)
				throw new Error("Cannot create ad-hoc MediaSize instances");
			this._value = value;
		}
		
		public static function forName(name:String):MediaSize {
			return ALL_VALUES[name];
		}
		
		public function toString():String {
			return "[MediaSize] '" + _value + "'";
		}
		
		public function get value():String {
			return _value;
		}
	}
}
