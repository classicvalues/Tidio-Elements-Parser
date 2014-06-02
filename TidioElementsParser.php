<?php

if(!class_exists('phpQuery'))

	require 'TidioElementsParser/PhpQuery.php';
	
//

class TidioElementsParser {
	
	private static $cache = false;
	private static $apiData = null;
	private static $apiHost = 'http://apps.tidioelements.com/api-parse-data/';
	private static $projectPublicKey;
	private static $editedElements = array();
	private static $sitePath;
	private static $htmlElements = array();
		
	public static function start($projectPublicKey){
			
		self::$sitePath = $_SERVER['REQUEST_URI'];
		
		if(empty(self::$sitePath)){
			self::$sitePath = '/';
		}
						
		//		
				
		self::$projectPublicKey = $projectPublicKey;
				
		self::loadApiData();
					
		if(!self::$apiData)
			
			return false;
																			
		ob_start('TidioElementsParser::render');
		
	}

	public static function end(){

		if(!self::$apiData)
			
			return false;
		
		@ob_end_flush();
		
		
	}
	
	public static function render($html){
		
		$doc = phpQuery::newDocument($html);
		
		//
		
		$docHtml = $doc->find('html');
		
		if(!$docHtml->attr('id')){
			
			$docHtml->attr('id', 'tidio-editor-page');
			
		}
		
		// Plugin List
		
		if(!empty(self::$apiData)){
		
			$editedElements = array();
					
			foreach(self::$apiData['wysiwyg_elements'] as $e){
					
				// compare url	
				
				if(!self::compareURL($e)){
					
					continue;
					
				}
								
				//	
					
				$selector = $e['selector'];
					
				$ele = $doc->find($selector);
					
				if(!$ele->length)
						
					continue;
				
				//
				
				if($e['type']=='edit'){
					$ele->html($e['html']);
				}
				
				if($e['type']=='delete'){
					$ele->remove();
				}
				
				$editedElements[] = $e['id'];
				
			}
			
			self::$editedElements = $editedElements;
		
		}
				
		// wp - compability mode
		
		if(class_exists('TidioPluginsScheme')){
			
			foreach(TidioPluginsScheme::$insertCode as $e){
				self::$htmlElements[] = array(
					'placement' => 'prependHead',
					'html' => $e
				);
			}
			
		}
						
		// Append JavaScript
		
		$head = $doc->find('head');
		
		//
				
		$head->prepend('<script type="text/javascript" src="//www.tidioelements.com/redirect/'.self::$projectPublicKey.'.js"></script>');		

		$head->prepend('<script type="text/javascript">var tidioElementsEditedElements = '.json_encode(self::$editedElements).';</script>');
		
		//

		foreach(self::$htmlElements as $e){
			if($e['placement']=='prependHead'){
				$head->prepend($e['html']);
			}
		}
		
		// Render HTML
		
		return $doc->htmlOuter();
		
	}
	
	public static function addHtml($html, $placement = 'prependHead'){
		
		self::$htmlElements[] = array(
			'html' => $html,
			'placement' => $placement
		);
		
	}
	
	private static function compareURL($e){
		
		if($e['url_global']){	
			return true;
		}
		
		if(!is_array($e['url']) && $e['url']==self::$sitePath){
			return true;
		}
		
		return false;
		
	}
		
	private static function loadApiData(){
		
		$apiUrl = self::$apiHost.'?projectPublicKey='.self::$projectPublicKey;
				
		$apiData = self::loadUrlData($apiUrl);
		
		if(!$apiData)
			
			return false;
			
		$apiData = json_decode($apiData, true);
		
		if(!$apiData || !$apiData['status'])
			
			return false;
			
		//
			
		self::$apiData = $apiData['value'];
		
		if(self::$apiData==null){	
			self::$apiData = true;
		}
		
		
		return true;		
	}
	
	private static function loadUrlData($url){
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.154 Safari/537.36');
		$content = curl_exec($ch);
		curl_close($ch);
		
		return $content;
		
	}
	
}