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

package org.flowplayer.config {

	/**
	 * @author api
	 */
	public class VersionInfo {
		private static const VERSION_NUMBER:String = CONFIG::version1 + "." + CONFIG::version2 + "." + CONFIG::version3;
		
		private static const VERSION_INFO:String = (CONFIG::commercialVersion ? "Flowplayer commercial version " :
		"Flowplayer free version " ) + VERSION_NUMBER + (CONFIG::versionStatus ? "-" + CONFIG::versionStatus : "");

		public static function get version():Array {
			return [new int(CONFIG::version1), new int(CONFIG::version2), new int(CONFIG::version3), CONFIG::commercialVersion ? 'commercial' : 'free', CONFIG::versionStatus];
		}
		
		public static function versionInfo():String {
			return VERSION_INFO;
		}
		
		public static function get commercial():Boolean {
			return CONFIG::commercialVersion;
		}
		
		public static function get controlsVersion():String {
			return CONFIG::controlsVersion;
		}
		
		public static function get audioVersion():String {
			return CONFIG::audioVersion;
		}
	}
	
}
