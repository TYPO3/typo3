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
	/**
	 * @author api
	 */
	internal class WrapperForIE {
		private var _player:Flowplayer;

		public function WrapperForIE(player:Flowplayer) {
			_player = player;
		}

		public function fp_stop():void {
			_player.stop();
		}

		public function fp_pause():void {
			_player.pause();
		}

		public function fp_resume():void {
			_player.resume();
		}

		public function fp_close():void {
			_player.close();
		}

	}
}
