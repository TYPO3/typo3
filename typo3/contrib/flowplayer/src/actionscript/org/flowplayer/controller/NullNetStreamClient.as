/*    
 *    Copyright 2008 Anssi Piirainen
 *
 *    This file is part of FlowPlayer.
 *
 *    FlowPlayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    FlowPlayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with FlowPlayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.controller {
	import org.flowplayer.controller.NetStreamCallbacks;
	
	/**
	 * @author api
	 */
	internal class NullNetStreamClient implements NetStreamCallbacks {
		public function onCuePoint(infoObject:Object):void {
		}
		
		public function onXMPData(infoObject:Object):void {
		}
		
		public function onBWDone(infoObject:Object):void {
		}
		
		public function onCaption(cps:String, spk:Number):void {
		}
		
		public function onCaptionInfo(infoObject:Object):void {
		}
		
		public function onFCSubscribe(infoObject:Object):void {
		}
		
		public function onLastSecond(infoObject:Object):void {
		}
		
		public function onPlayStatus(infoObject:Object):void {
		}
		
		public function onImageData(infoObject:Object):void {
		}
		
		public function RtmpSampleAccess(infoObject:Object):void {
		}
		
		public function onTextData(infoObject:Object):void {
		}
		
		public function onMetaData(infoObject:Object):void {
		}
	}
}
