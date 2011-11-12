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

package org.flowplayer.view {
	import org.flowplayer.model.Clip;
	import org.flowplayer.model.MediaSize;
	import org.flowplayer.util.Log;	

	/**
	 * @author api
	 */
	internal class MediaResizer {

		private var log:Log = new Log(this);
		private var _clip:Clip;
		private var _maxWidth:int;
		private var _maxHeight:int;
		private var _currentSizingOption:MediaSize;

		public function MediaResizer(clip:Clip, maxWidth:int, maxHeight:int) {
			this._clip = clip;
			this._maxWidth = maxWidth;
			this._maxHeight = maxHeight;
			_currentSizingOption = MediaSize.FITTED_PRESERVING_ASPECT_RATIO;
		}
		
		public function setMaxSize(width:int, height:int):void {
			this._maxWidth = width;
			this._maxHeight = height;
		}

		public function resizeTo(sizingOption:MediaSize, force:Boolean = false):Boolean {
            log.debug("resizeTo() " + sizingOption);
			if (sizingOption == null)
				sizingOption = _currentSizingOption;
			
			var resized:Boolean = false;
			if (sizingOption == MediaSize.FITTED_PRESERVING_ASPECT_RATIO) {
				resized = resizeToFit();
			} else if (sizingOption == MediaSize.HALF_FROM_ORIGINAL) {
				resized = resizeToHalfAvailableSize();
			} else if (sizingOption == MediaSize.ORIGINAL) {
				resized = resizeToOrig(force);
            } else if (sizingOption == MediaSize.FILLED_TO_AVAILABLE_SPACE) {
                resized = resizeToMax();
            } else if (sizingOption == MediaSize.CROP_TO_AVAILABLE_SPACE) {
                resized = resizeToFit(true);
            }
			_currentSizingOption = sizingOption;
			return resized;
		}

        private function resizeToFit(allowCrop:Boolean = false):Boolean {
            if (origWidth == 0 || origHeight == 0) {
                log.warn("resizeToFit: original sizes not available, will not resize");
                return false;
            }
            log.debug("resize to fit, original size " + _clip.originalWidth + "x" + _clip.originalHeight + ", will crop? " + allowCrop);

            var xRatio:Number = _maxWidth / origWidth;
            var useXRatio:Boolean = allowCrop ? xRatio * origHeight > _maxHeight : xRatio * origHeight <= _maxHeight;

            log.debug("using " + (useXRatio ? "x-ratio" : "y-ratio"));
            if (useXRatio) {
                resize(_maxWidth, calculateFittedDimension(_maxHeight, origHeight, xRatio));
            } else {
                var yRatio:Number = _maxHeight / origHeight;
                resize(calculateFittedDimension(_maxWidth, origWidth, yRatio), _maxHeight);
            }
            return true;
        }

        private function calculateFittedDimension(maxLength:int, origLength:int,  scalingFactor:Number):int {
            var result:int = Math.ceil(scalingFactor * origLength);
            return result > maxLength ? maxLength : result;
        }

		public function scale(scalingFactor:Number):void {
			resize(scalingFactor * origWidth, scalingFactor * origHeight);
		}
	
		private function resizeToOrig(force:Boolean = false):Boolean {
			if (force) {
				resize(origWidth, origHeight);
				return true;
			}
			if (origHeight > _maxHeight || origWidth > _maxWidth) {
				log.warn("original size bigger that mas size! resizeToOrig() falls to resizeToFit()");
				return resizeToFit();
			} else if (origWidth && origHeight) {
				log.debug("resize to original size");
				resize(origWidth, origHeight);
				return true;
			} else {
				log.warn("resizeToOrig() cannot resize to original size because original size is not available");
				return false;
			}
		}
	
		private function resizeToHalfAvailableSize():Boolean {
			log.debug("resize to half");
			scale((_maxWidth / 2) / origWidth);
			return true;
		}
		
		private function resizeToMax():Boolean {
			log.debug("resizing to max size (filling available space)");
			resize(_maxWidth, _maxHeight);
			return true;
		}
	
		private function resize(newWidth:int, newHeight:int):void {
            log.debug("resizing to " + newWidth + "x" + newHeight);
			_clip.width = newWidth;
			_clip.height = newHeight;			
            log.debug("resized to " + _clip.width + "x" + _clip.height);
		}
	
		public function get currentSize():MediaSize {
			return _currentSizingOption;
		}
		
		public function hasOrigSize():Boolean {
			return origHeight > 0 && origWidth > 0;
		}
		
		public function toString():String {
			return "[MediaResizer] origWidth: " + origWidth + ", origHeight: " + origHeight + ", maxWidth: " + _maxWidth + ", maxHeight: " + _maxHeight;
		}

		public function get origWidth():int {
			return _clip.originalWidth;
		}

		public function get origHeight():int {
			return _clip.originalHeight;
		}
	}
}
