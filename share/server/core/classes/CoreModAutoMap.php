<?php
/*******************************************************************************
 *
 * CoreModAutoMap.php - Core Automap module to handle ajax requests
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModAutoMap extends CoreModule {
	private $name = null;
	private $aOpts = null;
	private $opts = null;
	private $MAPCFG  = null;
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		$this->aOpts = Array('show' => MATCH_MAP_NAME,
		               'backend' => MATCH_STRING_NO_SPACE_EMPTY,
		               'root' => MATCH_STRING_NO_SPACE_EMPTY,
		               'maxLayers' => MATCH_INTEGER_PRESIGN_EMPTY,
		               'childLayers' => MATCH_INTEGER_PRESIGN_EMPTY,
		               'parentLayers' => MATCH_INTEGER_PRESIGN_EMPTY,
		               'renderMode' => MATCH_AUTOMAP_RENDER_MODE,
		               'width' => MATCH_INTEGER_EMPTY,
		               'height' => MATCH_INTEGER_EMPTY,
		               'ignoreHosts' => MATCH_STRING_NO_SPACE_EMPTY,
		               'filterGroup' => MATCH_STRING_EMPTY,
		               'filterByState' => MATCH_STRING_NO_SPACE_EMPTY);
		
		$aVals = $this->getCustomOptions($this->aOpts);
		$this->name = $aVals['show'];
		unset($aVals['show']);
		$this->opts = $aVals;
		
		// Save the automap name to use
		$this->opts['automap'] = $this->name;
		// Save the preview mode (Enables/Disables printing of errors)
		$this->opts['preview'] = 0;
		
		// Register valid actions
		$this->aActions = Array(
			'parseAutomap' => REQUIRES_AUTHORISATION,
			'getAutomapProperties' => REQUIRES_AUTHORISATION,
			'getAutomapObjects' => REQUIRES_AUTHORISATION,
			'getObjectStates' => REQUIRES_AUTHORISATION,
			'automapToMap' => REQUIRES_AUTHORISATION,
			'modifyParams' => REQUIRES_AUTHORISATION,
			'parseMapCfg' => REQUIRES_AUTHORISATION,
		);
		
		// Register valid objects
		$this->aObjects = $this->CORE->getAvailableAutomaps();
		
		// Set the requested object for later authorisation
		$this->setObject($this->name);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'parseAutomap':
				case 'parseMapCfg':
					$sReturn = $this->parseAutomap();
				break;
				case 'getAutomapProperties':
					$sReturn = $this->getAutomapProperties();
				break;
				case 'getAutomapObjects':
					$sReturn = $this->getAutomapObjects();
				break;
				case 'getObjectStates':
					$sReturn = $this->getObjectStates();
				break;
				case 'automapToMap':
					$VIEW = new NagVisViewAutomapToMap($this->CORE);
          $sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
				case 'modifyParams':
					$VIEW = new NagVisViewAutomapModifyParams($this->CORE, $this->opts);
					$sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function parseAutomap() {
		// Initialize backends
		$BACKEND = new CoreBackendMgmt($this->CORE);
		
		$MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
		$MAPCFG->readMapConfig();
		
		$MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $this->opts, IS_VIEW);

		if($this->sAction == 'parseAutomap') {
			$MAP->renderMap();
			return json_encode(true);
		} else {
			$FHANDLER = new CoreRequestHandler($_POST);
			if($FHANDLER->match('target', MATCH_MAP_NAME)) {
				$target = $FHANDLER->get('target');
			
				if($MAP->toClassicMap($target)) {
					new GlobalMessage('NOTE', 
														$this->CORE->getLang()->getText('The map has been created.'),
														null,
														null,
														1,
														$this->CORE->getMainCfg()->getValue('paths','htmlbase').'/frontend/nagvis-js/index.php?mod=Map&show='.$target);
				}  else {
					new GlobalMessage('ERROR', $this->CORE->getLang()->getText('Unable to create map configuration file.'));
				}
			} else {
				new GlobalMessage('ERROR', $this->CORE->getLang()->getText('Invalid target option given.'));
			}
		}
	}
	
	private function getAutomapProperties() {
		$MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
		$MAPCFG->readMapConfig();
		
		$arr = Array();
		$arr['map_name'] = $MAPCFG->getName();
		$arr['alias'] = $MAPCFG->getValue('global', 0, 'alias');
		$arr['background_image'] = $this->CORE->getMainCfg()->getValue('paths', 'htmlsharedvar').$MAPCFG->getName().'.png?'.mt_rand(0,10000);
		$arr['background_usemap'] = '#automap';
		$arr['background_color'] = $MAPCFG->getValue('global', 0, 'background_color');
		$arr['favicon_image'] = $this->CORE->getMainCfg()->getValue('paths', 'htmlimages').'internal/favicon.png';
		$arr['page_title'] = $MAPCFG->getValue('global', 0, 'alias').' ([SUMMARY_STATE]) :: '.$this->CORE->getMainCfg()->getValue('internal', 'title');
		$arr['event_background'] = $MAPCFG->getValue('global', 0, 'event_background');
		$arr['event_highlight'] = $MAPCFG->getValue('global', 0, 'event_highlight');
		$arr['event_highlight_interval'] = $MAPCFG->getValue('global', 0, 'event_highlight_interval');
		$arr['event_highlight_duration'] = $MAPCFG->getValue('global', 0, 'event_highlight_duration');
		$arr['event_log'] = $MAPCFG->getValue('global', 0, 'event_log');
		$arr['event_log_level'] = $MAPCFG->getValue('global', 0, 'event_log_level');
		$arr['event_log_height'] = $MAPCFG->getValue('global', 0, 'event_log_height');
		$arr['event_log_hidden'] = $MAPCFG->getValue('global', 0, 'event_log_hidden');
		$arr['event_scroll'] = $MAPCFG->getValue('global', 0, 'event_scroll');
		$arr['event_sound'] = $MAPCFG->getValue('global', 0, 'event_sound');
		
		return json_encode($arr);
	}
	
	private function getAutomapObjects() {
		// Initialize backends
		$BACKEND = new CoreBackendMgmt($this->CORE);
		
		$MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
		$MAPCFG->readMapConfig();
		
		$MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $this->opts, IS_VIEW);
		
		// Read position from graphviz and set it on the objects
		$MAP->setMapObjectPositions();
		return $MAP->parseObjectsJson();
	}

	private function getObjectStates() {
		$arrReturn = Array();
		
		$aOpts = Array('ty' => MATCH_GET_OBJECT_TYPE,
		               't' => MATCH_OBJECT_TYPES,
		               'n1' => MATCH_STRING,
		               'n2' => MATCH_STRING_EMPTY,
		               'i' => MATCH_STRING_NO_SPACE);
		
		$aVals = $this->getCustomOptions($aOpts);
		
		$sType = $aVals['ty'];
		$arrType = $aVals['t'];
		$arrName1 = $aVals['n1'];
		$arrName2 = $aVals['n2'];
		$arrObjId = $aVals['i'];
		
		// Initialize backends
		$BACKEND = new CoreBackendMgmt($this->CORE);
		
		// Read the map configuration file
		$this->MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
		$this->MAPCFG->readMapConfig();
		
		$numObjects = count($arrType);
		$aObjs = Array();
		for($i = 0; $i < $numObjects; $i++) {
			// Get the object configuration
			$objConf = $this->getObjConf($arrType[$i], $arrName1[$i], $arrName2[$i]);
			
			// The object id needs to be set here to identify the object in the response
			$objConf['object_id'] = $arrObjId[$i];
			
			switch($arrType[$i]) {
				case 'host':
					$OBJ = new NagVisHost($this->CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i]);
				break;
				case 'service':
					$OBJ = new NagVisService($this->CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i], $arrName2[$i]);
				break;
				case 'hostgroup':
					$OBJ = new NagVisHostgroup($this->CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i]);
				break;
				case 'servicegroup':
					$OBJ = new NagVisServicegroup($this->CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i]);
				break;
				case 'map':
					// Initialize map configuration based on map type
					$MAPCFG = new NagVisMapCfg($this->CORE, $arrName1[$i]);
					$MAPCFG->readMapConfig();
					
					$OBJ = new NagVisMapObj($this->CORE, $BACKEND, $MAPCFG, !IS_VIEW);
				break;
				case 'automap':
					// Initialize map configuration based on map type
					$MAPCFG = new NagVisAutomapCfg($this->CORE, $arrName1[$i]);
					$MAPCFG->readMapConfig();
					
					$MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, Array('automap' => $arrName1[$i], 'preview' => 1), !IS_VIEW);
					$OBJ = $MAP->MAPOBJ;
				break;
				default:
					echo 'Error: '.$this->CORE->getLang()->getText('unknownObject', Array('TYPE' => $arrType[$i], 'MAPNAME' => ''));
				break;
			}
			
			// Apply default configuration to object
			$OBJ->setConfiguration($objConf);
			
			if($arrType[$i] != 'automap') {
				$OBJ->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
			}
			
			$aObjs[] = $OBJ;
		}
			
		// Now after all objects are queued execute them and then apply the states
		$BACKEND->execute();
	
		foreach($aObjs AS $OBJ) {
			$OBJ->applyState();
			$OBJ->fetchIcon();
		
			switch($sType) {
				case 'state':
					$arr = $OBJ->getObjectStateInformations();
				break;
				case 'complete':
					$arr = $OBJ->parseJson();
				break;
			}
			
			$arr['object_id'] = $OBJ->getObjectId();
			$arr['icon'] = $OBJ->get('icon');
			
			$arrReturn[] = $arr;
		}
			
		return json_encode($arrReturn);
	}

	private function getObjConf($objType, $objName1, $objName2 = '') {
		$objConf = Array();
		
		// Default object configuration
		$objConf = $this->MAPCFG->getObjectConfiguration($objName1);

		// backend_id is filtered in getObjectConfiguration(). Set it manually
		$objConf['backend_id'] = $this->MAPCFG->getValue('global', 0, 'backend_id');
			
		return $objConf;
	}
}
?>
