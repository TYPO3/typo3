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
    import com.adobe.utils.StringUtil;
import flash.display.LoaderInfo;
	import flash.external.ExternalInterface;
    import flash.net.URLRequest;
    import flash.net.navigateToURL;

    /**
	 * @author anssi
	 */
	public class URLUtil {
        private static var _log:Log = new Log("org.flowplayer.util::URLUtil");
        private static var _loaderInfo:LoaderInfo;

		
		public static function completeURL(baseURL:String, fileName:String):String {
			return addBaseURL(baseURL || pageLocation || playerBaseUrl, fileName);
		}

		public static function addBaseURL(baseURL:String, fileName:String):String {
			if (fileName == null) return null;
			
			if (isCompleteURLWithProtocol(fileName)) return fileName;
			if (fileName.indexOf("/") == 0) return fileName;
			
			if (baseURL == '' || baseURL == null || baseURL == 'null') {
				return fileName;
			}
			if (baseURL != null) {
				if (baseURL.lastIndexOf("/") == baseURL.length - 1)
					return baseURL + fileName;
				return baseURL + "/" + fileName;
			}
			return fileName;
		}

        public static function appendToPath(base:String, postFix:String):String {
            if (StringUtil.endsWith(base, "/")) return base + postFix;
            return base + "/" + postFix;
        }

		public static function isCompleteURLWithProtocol(fileName:String):Boolean {
			if (! fileName) return false;
			return fileName.indexOf("://") > 0;
		}
		

        private static function detectPageUrl(functionName:String):String {
            _log.debug("detectPageUrl() " + functionName);
            try {
                return ExternalInterface.call(functionName);
            } catch (e:Error) {
                _log.debug("Error in detectPageUrl() " + e);
            }
            return null;
        }

        public static function get pageUrl():String {
            if (!ExternalInterface.available) return null;

            var href:String = detectPageUrl("window.location.href.toString");
            if (! href || href == "") {
                href = detectPageUrl("document.location.href.toString");
            }
            if (! href || href == "") {
                href = detectPageUrl("document.URL.toString");
            }
            return href;
        }

        public static function get pageLocation():String {
            var url:String = pageUrl;
            return url ? baseUrlAndRest(url)[0] : null;
        }

        public static function baseUrlAndRest(url:String):Array {
            var endPos:int = url.indexOf("?");
            if (endPos > 0) {
                endPos = url.substring(0, endPos).lastIndexOf("/");
            } else if ( url.indexOf('#') != -1 ) {	// #112, when you have a / afer a #
                endPos = url.substring(0, url.indexOf('#')).lastIndexOf("/");
            } else {
				endPos = url.lastIndexOf("/");
			}
            if (endPos > 0) {
                return [url.substring(0, endPos), url.substring(endPos + 1)];
            } else {
                return [null, url];
            }
        }
		
		public static function get playerBaseUrl():String {
			var url:String = _loaderInfo.url;
			var firstSwf:Number = url.indexOf(".swf");
			url = url.substring(0, firstSwf);
			var lastSlashBeforeSwf:Number = url.lastIndexOf("/");
			return url.substring(0, lastSlashBeforeSwf);
		}
		
		public static function localDomain(url:String):Boolean {
			if (url.indexOf("http://localhost/") == 0) return true;
            if (url.indexOf("file://") == 0) return true;
            if (url.indexOf("chrome://") == 0) return true;
			if (url.indexOf("http://127.0.0.1") == 0) return true;
			if (url.indexOf("http://") == 0) return false;
			if (url.indexOf("/") == 0) return true;
			return false;
		}

        public static function set loaderInfo(value:LoaderInfo):void {
            _loaderInfo = value;
        }

        public static function openPage(url:String, linkWindow:String = "_blank", popUpDimensions:Array = null):void {
            if (linkWindow == "_popup" && ExternalInterface.available) {
                _log.debug("openPage(), opening popup");
                var dimensions:Array = popUpDimensions || [800,600];
                ExternalInterface.call("window.open('" + url + "','PopUpWindow','width=" + dimensions[0] + ",height=" + dimensions[1] + ",toolbar=yes,scrollbars=yes')");
            } else {
                // Use JS to bypass popup blockers if ExternalInterface is available
                var window:String = linkWindow == "_popup" ? "_blank" : linkWindow;
                if (ExternalInterface.available) {
                    ExternalInterface.call('window.open("' + url + '","' + window + '")');
                } else {
                    //request a blank page
                    navigateToURL(new URLRequest(url), window);
                }
            }
        }
    }
}
