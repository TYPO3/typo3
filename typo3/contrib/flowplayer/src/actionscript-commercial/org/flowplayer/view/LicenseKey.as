/*    
 *    Copyright 2008 Anssi Piirainen
 *
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

package org.flowplayer.view {
	import org.flowplayer.util.Log;	
	

	CONFIG::commercialVersion {
		import org.flowplayer.FlowplayerLicenseKey;
	}		

	CONFIG::commercialVersion
	public class LicenseKey {
		private static var log:Log = new Log("org.flowplayer.view::LicenseKey");

		public static function validate(swfUrl:String, version:Array, configuredKeys:Object, externalInterfaceAvailable:Boolean):Boolean {
			trace("using validator " + FlowplayerLicenseKey.id);
			return FlowplayerLicenseKey.validate(swfUrl, version, configuredKeys, externalInterfaceAvailable);
		}
	}
	
	CONFIG::freeVersion
	public class LicenseKey {

		public static function validate(swfUrl:String, version:Array, configuredKeys:Object, externalInterfaceAvailable:Boolean):Boolean {
			return true;
		}
	}

}
