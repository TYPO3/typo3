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

package org.flowplayer.view {
	import flash.display.Sprite;
	import flash.filters.GlowFilter;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;
	import flash.text.TextFormat;
	
	import org.flowplayer.LabelHolderLeft;
	import org.flowplayer.util.Arrange;
	import org.flowplayer.view.AbstractSprite;	

	/**
	 * @author api
	 */
	internal class LabelPlayButton extends AbstractSprite {

		private var _label:TextField;
		private var _labelHolder:Sprite;
		private var _labelHolderLeft:Sprite;
		private var _labelHolderRight:Sprite;
		private var _player:Flowplayer;
		private var _resizeToTextWidth:Boolean;

		public function LabelPlayButton(player:Flowplayer, label:String, adjustToTextWidth:Boolean = true) {
			_player = player;
			_resizeToTextWidth = adjustToTextWidth;
			createChildren(label);
		}
		
		public function setLabel(value:String, changeWidth:Boolean = true):void {
			log.debug("setLabel, changeWidth " + changeWidth);
			if (_label.text == value) return;
			_resizeToTextWidth = changeWidth;
			_label.text = value;
			onResize();
		}
		
		private function createChildren(label:String):void {
			_labelHolderLeft = new LabelHolderLeft();
			addChild(_labelHolderLeft);
			_labelHolder = new LabelHolder();
			addChild(_labelHolder);

			_labelHolderRight =  new LabelHolderRight();
			addChild(_labelHolderRight);

			_label = _player.createTextField();
			_label.textColor = 0xffffff;
			_label.selectable = false;
			_label.autoSize = TextFieldAutoSize.RIGHT;
			_label.multiline = false;
			_label.text = label;
			_label.width = _label.textWidth;

			var labelGlow:GlowFilter = new GlowFilter(0xFFFFFF, .30, 4, 4, 3, 3);
			var labelFilters:Array = [labelGlow];
			_label.filters = labelFilters;

			addChild(_label);
		}
		
		override protected function onResize():void {
			log.debug("arranging label");
			_labelHolderRight.height = height;
			_labelHolderRight.scaleX = _labelHolderRight.scaleY;

			_labelHolderLeft.height = height;
			_labelHolderLeft.scaleX = _labelHolderLeft.scaleY;
			
			var format:TextFormat = _label.defaultTextFormat;
			format.size = _labelHolder.height/3;
			_label.setTextFormat(format);

			_labelHolder.width = int(_resizeToTextWidth ? _label.textWidth+10 : (width - _labelHolderRight.width - _labelHolderLeft.width));
			_labelHolder.height = height;

			Arrange.center(_labelHolder, width, height);
			_labelHolderLeft.x = _labelHolder.x - _labelHolderLeft.width;
			_labelHolderRight.x = _labelHolder.x + _labelHolder.width;

			Arrange.center(_labelHolderLeft, 0, height);
			Arrange.center(_labelHolderRight, 0, height);
			Arrange.center(_label, 0, height);

			_label.x = _labelHolder.x + _labelHolder.width / 2 - _label.width / 2;
		}
	}
}
