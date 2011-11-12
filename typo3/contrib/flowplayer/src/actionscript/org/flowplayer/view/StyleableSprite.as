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
    import flash.events.MouseEvent;
    import flash.net.URLRequest;
import flash.net.navigateToURL;
	import org.flowplayer.controller.ResourceLoader;
	import org.flowplayer.controller.ResourceLoaderImpl;
	import org.flowplayer.layout.Length;
	import org.flowplayer.util.GraphicsUtil;
	import org.flowplayer.view.AbstractSprite;
	import org.flowplayer.view.ErrorHandler;
	import org.flowplayer.view.FlowStyleSheet;
	
	import flash.display.Bitmap;
	import flash.display.DisplayObject;
	import flash.display.DisplayObjectContainer;
	import flash.display.Graphics;
	import flash.display.Loader;
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.utils.getDefinitionByName;
	import flash.utils.getQualifiedClassName;	

	/**
	 * A sprite that can be styled using a StyleSheet. The stylesheet can specify a background image
	 * to be used or alternatively a background color gradient.
	 * 
	 * @author api
	 */
	public class StyleableSprite extends AbstractSprite implements Styleable {

		private var _style:FlowStyleSheet;
		private var _image:DisplayObject;
		private var _imageMask:DisplayObject;
		private var _imageHolder:Sprite;
		private var _errorHandler:ErrorHandler;
		private var _border:Sprite;
		private var _redrawing:Boolean;
		private var _loader:ResourceLoader;

		/**
		 * Creates a new StyleableSprite.
		 */
		public function StyleableSprite(styleName:String = null, errorHandler:ErrorHandler = null, loader:ResourceLoader = null) {
			_errorHandler = errorHandler;
			_loader = loader;
			if (styleName && loader) {
				_style = new FlowStyleSheet(styleName);
				loadOrDrawBackground();
			}
		}

		/**
		 * Redraws the sprite, by calling <code>redraw()</code>. Overriding method should call either <code>super.onResize()</code>
		 * to properly have the background occupy the who space.
		 */
		override public function setSize(width:Number, height:Number):void {
			super.setSize(width, height);
			redraw();			
		}
		
		/**
		 * Called when the background has been redrawn.
		 */
		protected function onRedraw():void {
		}
		
		private function redraw():void {
			if (! style) {
				onRedraw();
				return;
			}
			drawBackground();
			arrangeBackgroundImage();
			drawBorder();
			setChildIndexes();
            addLinkListener();
			onRedraw();
			_redrawing = false;
		}

        private function addLinkListener():void {
            setLinkListener(this, false);
            setLinkListener(_imageHolder, false);

            if (_style.linkUrl) {
                setLinkListener(_imageHolder || this, true);
            }
        }

        private function setLinkListener(parent:Sprite, enable:Boolean):void {
            if (! parent) return;
            parent.buttonMode = enable;
            if (enable) {
                parent.addEventListener(MouseEvent.CLICK, onBackgroundImageClicked);
            } else {
                parent.removeEventListener(MouseEvent.CLICK, onBackgroundImageClicked);
            }
        }

		private function drawBackground():void {
			graphics.clear();
			if (! _style.backgroundTransparent) {
				log.debug("drawing background color " + _style.backgroundColor + ", alpha " + _style.backgroundAlpha);
				graphics.beginFill(_style.backgroundColor, _style.backgroundAlpha);
				GraphicsUtil.drawRoundRectangle(graphics, 0, 0, width, height, _style.borderRadius);
				graphics.endFill();
			} else {
				log.debug("background color is transparent");
			}
			if (_style.backgroundGradient) {
				log.debug("adding gradient");
				GraphicsUtil.addGradient(this, _imageHolder ? getChildIndex(_imageHolder) : 0,  _style.backgroundGradient, _style.borderRadius);
			} else {
				GraphicsUtil.removeGradient(this);
			}
		}
		
		private function setChildIndexes():void {
			if (_imageHolder) {
				setChildIndex(_imageHolder, 0);
			}
		}

		/**
		 * Sets a new stylesheet.
		 */
		public final function set style(style:FlowStyleSheet):void {
			log.debug("set style");
			_style = style;
			onSetStyle(style);
			loadOrDrawBackground();
		}
		
		protected function onSetStyle(style:FlowStyleSheet):void {
		}
		
		public function onBeforeCss(styleProps:Object = null):void 
		{
			
		}
		
		public function css(styleProps:Object = null):Object {
			_redrawing = true;
			log.debug("css " +styleProps);
			if (! styleProps) return _style.rootStyle;
			
			var rootStyle:Object = null;
			for (var propName:String in styleProps) {
				if (FlowStyleSheet.ROOT_STYLE_PROPS.indexOf(propName) >= 0) {
					if (! rootStyle) {
						rootStyle = new Object();	
					}
					log.debug(propName + " will affect root style, new value " + styleProps[propName]);
					rootStyle[propName] = styleProps[propName];
				} else {
					log.debug("updating style of " + propName);
					addStyleRules(propName, styleProps[propName]);
				}
			}
			if (rootStyle) {
				addStyleRules(_style.rootStyleName, rootStyle);
			}
			return _style.rootStyle;
		}
		
		private function addStyleRules(styleName:String, style:Object):void {
			if (styleName == _style.rootStyleName) {
				_style.addToRootStyle(style);
				onSetRootStyle(_style.rootStyle);
				loadOrDrawBackground();
			} else {
				_style.addStyleRules(styleName, style);
				onSetStyleObject(styleName, style);
			}
		}

		protected function onSetStyleObject(styleName:String, style:Object):void {
		}

		/**
		 * Sets the style properties object. This sprite is redrawn accoring
		 * to the new style properties.
		 * @see #onArranged()
		 */
		public final function set rootStyle(style:Object):void {
			log.debug("setting root style to " + this);
			if (! _style) {
				_style = new FlowStyleSheet(getQualifiedClassName(this));
			}
			_style.rootStyle = style;
			onSetRootStyle(style);
			loadOrDrawBackground();
		}
		
		public function addToRootStyle(style:Object):void {
			_style.addToRootStyle(style);
			onAddToRootStyle();
			loadOrDrawBackground();
		}
		
		private function onAddToRootStyle():void {
		}

		protected function onSetRootStyle(style:Object):void {
		}
		
		public final function get style():FlowStyleSheet {
			return _style;
		}
		
		private function loadOrDrawBackground():void {
			if (_style.backgroundImage) {
				log.debug("stylesheet specified a background image " + _style.backgroundImage);
				loadBackgroundImage();
			} else {
				_image = null;
				removeBackgroundImage();
				redraw();
			}
		}

		private function loadBackgroundImage():void {
			var image:String = _style.backgroundImage;
			if (! image) return;
			if (image.indexOf("url(") == 0) {
				image = image.substring(4, image.length - 1);
			}
			if (! _loader) {
				throw new Error("ResourceLoader not available, cannot load backgroundImage");
			}
			_loader.load(image, onImageLoaded);
		}
		
		private function onImageLoaded(loader:ResourceLoader):void {
			_image = loader.getContent() as DisplayObject;
			log.debug("received bacground image " + _image);
			redraw();
		}

		private function arrangeBackgroundImage():void {
			if (! _image) return;
//			graphics.clear();
			createImageHolder();

			if (_style.backgroundRepeat) {
				repeatBackground(_image);
			} else {
				addBackgroundImage(_image);
				var xPos:Length = _style.backgroundImageX;
				var yPos:Length = _style.backgroundImageY;
				
				log.debug("background image xPos " + xPos);
				log.debug("background image yPos " + yPos);
				
				if (xPos.px >= 0) {
					_imageHolder.x = xPos.px;
				} else if (xPos.pct > 0) {
					_imageHolder.x = xPos.pct/100 * width - _imageHolder .width/2;
				}
				
				if (yPos.px >= 0) {
					_imageHolder.y = yPos.px;
				} else if (yPos.pct > 0) {
					_imageHolder.y = yPos.pct/100 * height - _imageHolder .height/2;
				}
			}
		}
		
		private function removeBackgroundImage():Boolean {
			if (_imageHolder) {
				log.debug("removing background image");
				removeChild(_imageHolder);
				_imageHolder = null;
				return true;
			}
			return false;
		}
		
		private function createImageHolder():void {
			removeBackgroundImage();
			_imageHolder = new Sprite();
			addChild(_imageHolder);
			_imageMask = createMask();
			addChild(_imageMask);
			_imageHolder.mask = _imageMask;
			_imageHolder.x = 0;
			_imageHolder.y = 0;
		}

        private function onBackgroundImageClicked(event:MouseEvent):void {
            navigateToURL(new URLRequest(_style.linkUrl), _style.linkWindow);
            event.stopPropagation();
		}
		
		/**
		 * Creates a sprite that is equal to the size of this sprite.
		 * @return a sprite that can be used as a mask to hide display objects
		 * that exceed of go outside the borders of this sprite
		 */
		protected function createMask():Sprite {
			var mask:Sprite = new Sprite();
			mask.graphics.beginFill(0);
			GraphicsUtil.drawRoundRectangle(mask.graphics, 0, 0, width, height, _style.borderRadius);
			return mask;
		}

		private function addBackgroundImage(image:DisplayObject):DisplayObject {
			_imageHolder.addChild(image);
			return image;
		}

		private function repeatBackground(image:DisplayObject):void {
			var xMax:int  = Math.round(width/image.width);
			var yMax:int = Math.round(height/image.height);
			log.debug(xMax + ", " + yMax);
			for (var x:int = 0; x <= xMax; x++) {
				for (var y:int = 0; y <= yMax; y++) {
					var clone:DisplayObject = clone(image);
					// make sure cloning succeeded
					if (! clone) return;
					
					var child:DisplayObject = addBackgroundImage(clone);
					child.x = x * image.width;
					child.y = y * image.height;
					log.debug("added backgound at " + child.x + ", " + child.y);
				}
			}							
		}
		
		private function clone(target:DisplayObject):DisplayObject {
			if (! target) return null;
			if (target is Bitmap) return new Bitmap(Bitmap(target).bitmapData);
			if (target is Loader) return clone(Loader(target).content);
			
			var ClassReference:Class = getDefinitionByName(getQualifiedClassName(target)) as Class;
			return new ClassReference() as DisplayObject;
		}
		
		private function drawBorder():void {
			if (_border && _border.parent == this) {
				removeChild(_border);
			}
			if (! _style.borderWidth > 0) return;
			_border = new Sprite();
			addChild(_border);
			log.info("border weight is " + _style.borderWidth + ", alpha "+ _style.borderAlpha);		
			_border.graphics.lineStyle(_style.borderWidth, _style.borderColor, _style.borderAlpha);
			GraphicsUtil.drawRoundRectangle(_border.graphics, 0, 0, width, height, _style.borderRadius);
		}
		
		protected function get bgImageHolder():Sprite {
			return _imageHolder;
		}
			
		/**
		 * Currently just returns the root style object.
		 */
		
		public function onBeforeAnimate(styleProps:Object):void 
		{

		}
		
		public function animate(styleProps:Object):Object {
			return _style.rootStyle;
		}
		
		public function get redrawing() : Boolean{
			return _redrawing;
		}
		
		protected function set loader(loader:ResourceLoader):void {
			log.debug("got loader");
			_loader = loader;
		}		
	}
}
