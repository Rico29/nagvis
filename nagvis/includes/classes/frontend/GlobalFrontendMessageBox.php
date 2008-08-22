<?php
/*****************************************************************************
 *
 * GlobalFronendMessageBox.php - Class to render a messagebox in the frontend
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: michael_luebben@web.de)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/

/**
 * class GlobalFronendMessage
 *
 * @param   string   $type
 *			Follow type available:
 *				Message type for services:
 *					- ok		 		-> Green box for ok messages
 *					- warning 		-> Yellow box for warnings messages
 *					- unknown 		-> orange box for unknown messages
 *					- critical	 	-> Red box for errors messages
 *
 *				Message type for hosts:
 *					- up		 		-> Green box for up messages
 *					- down	 		-> Red box for errors messages
 *					- unknown 		-> orange box for unknown messages
 *					- unreachable 		-> orange box for unknown messages
 *
 *				Message type for another messages:
 *					- note 	 		-> Blue box for Information
 *					- error	 		-> Red box for errors messages
 *					- permission	-> Orange box for permission messages
 *
 * @param   string   $title	Title for the Box
 * @param   string   $message	Message
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalFrontendMessageBox {
	// Contains the page which be print out
	private $page;

	// Contains path to the html base
	private $pathHtmlBase;

	// This array contains allowed types of message boxes
	private $allowedTypes = array('error',
											'down',
											'critical',
											'up',
											'ok',
											'warning',
											'unknown',
											'unreachable',
											'note',
											'permission');

	//This variables contains informations which be used to build the message box
	private $type;
	private $message;
	private $title;

	/**
	 * The contructor check the type and build the message box
	 *
	 * @param   strind	$type
	 * @param   string	$message
	 * @param   string	$pathHtmlBase
	 * @param   string	$title
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function __construct($type, $message, $pathHtmlBase, $title = NULL) {
		$this->type = strtolower($type);
		$this->message = $message;
		$this->pathHtmlBase = $pathHtmlBase;
		
		if($title === NULL) {
			$this->title = $this->type;
		}
		
		// Check if type allowed else build error box
		if(!in_array($this->type, $this->allowedTypes)) {
			$CORE = new GlobalCore();
			$this->title = $CORE->LANG->getText('messageboxTitleWrongType');
			$this->message = $CORE->LANG->getText('messageboxMessageWrongType', 'TYPE~'.$this->wrongType);
		}
		
		// Got all informations, now build the box
		$this->buildMessageBox();
	}

	/**
	 * Build the message box
	 *
	 * @access  private
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	private function buildMessageBox() {
		$this->page .= '<style type="text/css"><!-- @import url('.$this->pathHtmlBase.'/nagvis/includes/css/frontendMessage.css);  --></style>'."\n";
		$this->page .= '<div id="messageBoxDiv"'."\n";
		$this->page .= '   <table id="messageBox" class="'.$this->type.'" height="100%" width="100%" cellpadding="0" cellspacing="0">'."\n";
		$this->page .= '      <tr>'."\n";
		$this->page .= '         <td class="'.$this->type.'" colspan="3" height="16">'."\n";
		$this->page .= '      </tr>'."\n";
		$this->page .= '      <tr height="32">'."\n";
		$this->page .= '         <th class="'.$this->type.'" align="center" width="60">'."\n";
		$this->page .= '           <img src="'.$this->pathHtmlBase.'/nagvis/images/internal/msg_'.$this->type.'.png"/>'."\n";
		$this->page .= '         </th>'."\n";
		$this->page .= '         <th class="'.$this->type.'">'.$this->title.'</td>'."\n";
		$this->page .= '         <th class="'.$this->type.'" align="center" width="60">'."\n";
		$this->page .= '           <img src="'.$this->pathHtmlBase.'/nagvis/images/internal/msg_'.$this->type.'.png"/>'."\n";
		$this->page .= '         </th>'."\n";
		$this->page .= '       </tr>'."\n";
		$this->page .= '       <tr>'."\n";
		$this->page .= '         <td class="'.$this->type.'" colspan="3" style="padding-top:16px;">'.$this->message.'</td>'."\n";
		$this->page .= '       </tr>'."\n";
		$this->page .= '   </table>'."\n";
		$this->page .= '</div>'."\n";
	}

	/**
	 * Print the message box
	 *
	 * return   String  HTML Code
	 * @access  private
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function __toString () {
		return $this->page;
	}
}
?>