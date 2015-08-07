<?php

class PhishOD_SMAPI {
	function getSessionId($args) {
		if (!$sessionid) {
			throw new SoapFault('Client.LoginUnauthorized',
								'MSG_SOAPFAULT_LOGIN_UNAUTHORIZED');			
		}
		
		logMsg(0, "getSessionId:  sid=" . $sessionid);

		return [
			'getSessionIdResult' => $sessionid
		];
	}

	function getMetadata($args) {
		
		logMsg(0, "getMetadata: " . $args->id);

		// Strip off the first word before ':'
		$idarray	  = $this->getID($args->id);		
		$args->prefix = array_shift($idarray);
		$args->id	 = array_shift($idarray);

		// Is there a getMD function for it?
		$func = "getMD_" . $args->prefix;
		logMsg(0, "$func: " . $args->id);

		if (!method_exists($this,$func)) {
			throw new SoapFault('Server.ItemNotFound',
								"MSG_SOAPFAULT_ITEM_NOT_FOUND" . ": $origid");
		}
		
		return array('getMetadataResult' => $this->$func($args));
	}

	function getExtendedMetadata($args) {

		logMsg(0, "getExtendedMetadata: " . $args->id);

		// Strip off the first word before ':' (convention in this script)
		$args->fullid = $args->id;
		$idarray	  = $this->getID($args->id);		
		$args->prefix = strtoupper(array_shift($idarray));
		$args->id	 = array_shift($idarray);

		// Is there a getMD function for it?
		$func = "getXMD_" . $args->prefix;		
		logMsg(1, "$func: " . $args->id);
		
		if (!method_exists($this,$func)) {
			throw new SoapFault('Server.ItemNotFound', "MSG_SOAPFAULT_ITEM_NOT_FOUND" . ": $origid");
		}
		
		return array('getExtendedMetadataResult' => $this->$func($args));
	}

	function getMediaMetadata($args) {
		
		logMsg(0, "getMediaMetadata: " . $args->id);
		
		$idarray	  = $this->getID($args->id);
		$args->prefix = strtoupper(array_shift($idarray));
		$id		   = array_shift($idarray);
		
		$tracks = $this->catalog->browseTrack($id);
		
		foreach ($tracks['data'] as $track) {
			return array('getMediaMetadataResult' => $this->mmdEntryFromTrack($track));
		}
		
		throw new SoapFault('Client.ItemNotFound', "MSG_SOAPFAULT_ITEM_NOT_FOUND");
	}

	function getMediaURI($args) {
		logMsg(0, "getMediaURI: " . $args->id);
		$url = $this->getMediaBaseURL() . "music.mp3";
		return array('getMediaURIResult' => $url);
	}

	function getScrollIndices($args) {
		$args;
		throw new SoapFault('Client.ServiceUnavailable', "MSG_SOAPFAULT_SERVICE_UNAVAILABLE" . " (getScrollIndices)");
	}

	function getLastUpdate($args) {
		return [
			'getLastUpdateResult' => [
				'catalog' => time(),
				'pollInterval' => 60 * 60
			]
		];
	}

}
