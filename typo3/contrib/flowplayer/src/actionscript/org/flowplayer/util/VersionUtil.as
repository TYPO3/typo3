package org.flowplayer.util {

	import flash.system.Capabilities;
	
	public class VersionUtil {
	
		public static function majorVersion():Number {
			return getVersion().majorVersion;
		}
		
		public static function minorVersion():Number {
			return getVersion().minorVersion;
		}
		
		public static function platform():String {
			return getVersion().platform;
		}
		
		public static function buildNumber():Number {
			return getVersion().buildNumber;
		}
		
		public static function getVersion():Object {
			var versionNumber:String = Capabilities.version;
			var versionArray:Array = versionNumber.split(",");
			
			var versionObj:Object = {};
			
			var platformAndVersion:Array = versionArray[0].split(" ");
			
			versionObj.platform = platformAndVersion[0];
			versionObj.majorVersion = parseInt(platformAndVersion[1]);
			versionObj.minorVersion = parseInt(versionArray[1]);
			versionObj.buildNumber = parseInt(versionArray[2]);
			
			return versionObj;
		}
		
		public static function isFlash10():Boolean {
			return VersionUtil.majorVersion() == 10;
		}
		
		public static function isFlash9():Boolean {
			return VersionUtil.majorVersion() == 9;
		}
		
		
	}
}
