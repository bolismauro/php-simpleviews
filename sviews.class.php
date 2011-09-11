<?php
/**
 * SViews GIT version
 * Requires PHP 5.3+
 * Copyright (c) 2011 Simone Lusenti
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class SViews {
	private $template_dir="templates";
	private $cache_dir="cache";
	private $javascript_base_dir = "includes/javascript";
	private $javascript_non_localized_script = "common";
	private $language = 'it';
	private $ldelim="{{";
	private $rdelim="}}";
	private $tag_rdelim="}";
	private $tag_ldelim="{";
	
	private $useCache = false;
	
	public function __construct($template_dir="") {
		//if (empty($template_dir)) {
			$this->template_dir=dirname(__FILE__).DIRECTORY_SEPARATOR.$this->template_dir;
		/*} else {
			$this->template_dir=dirname(__FILE__).DIRECTORY_SEPARATOR.$template_dir;
		}*/
		$this->cache_dir=dirname(__FILE__).DIRECTORY_SEPARATOR.$this->cache_dir;
		
		/**
		 * TODO manage localization
		 */
		$this->language = 'it';
	}
	
	public function render($template_name, array $context=array()) {
		
		//used to save the context's hash
		$actual_hash = null;
		
		
		if (empty($context)) echo '[warning: empty context provided]';
		
		$filename = $this->template_dir.DIRECTORY_SEPARATOR.$template_name;
		if (!file_exists($filename)) {
			throw new TemplateException("Template file does not exists: $filename");
			
		} else {
			
			// check if cache is enabled
			if($this->useCache){
				
				//the cache is enable, try to get a previous calculated view
				
				$hash = SViews::getArrayHash($context);
				
				$fileName = $template_name.$hash.".cache";
				
				//save the hash so we don't need to calculate it again
				$actual_hash = $hash;
				
				if(file_exists($this->cache_dir."/".$fileName)){
					return file_get_contents($this->cache_dir."/".$fileName);
				}	
				
			}
			
			
			$template = file_get_contents($filename);
			
			/* VARIABLES */
			
			//Variables - {{foo}}
			// - if the variable is not defined in $context, an exception will be thrown
			$template = preg_replace_callback('/'.$this->ldelim.'\s*(\w*)\s*'.$this->rdelim.'/i', function($matches) use ($context) {
					return SParser::_parseVar($matches, $context);	
				}, $template);
				
			//Dotted variables, includes method calls, arrays etc. {{foo.bar}}
			// - currently this is limited to one-level of dots. E.g.: class.method.method is *not* supported
			// - for {{foo.bar}} this order is used: $foo['bar'], $foo->bar(), $foo->getBar(), $foo->get('bar')
			// - if you need $foo.bar (where bar is an attribute) then you have something wrong in your mind
			// - case is preserved, except when calling getBar()
			$template = preg_replace_callback('/'.$this->ldelim.'\s*([\.\w]*)\s*'.$this->rdelim.'/i', function($matches) use ($context) {
					return SParser::_parseDottedVar($matches, $context);	
				}, $template);
			
			
			/* TAGS */
			
				
			//Javascript includes - {include script.js}
			$current_javascript_dir = $this->javascript_base_dir."/".$this->javascript_non_localized_script;
			$template = preg_replace_callback('/'.$this->tag_ldelim.'include\s+([a-zA-Z0-9\-\.]*\.js)\s*'.$this->tag_rdelim.'/i', function($matches) use ($current_javascript_dir) {
					return SParser::_parseJavascriptInclude($matches, $current_javascript_dir);	
				}, $template);				

			//Javascript localized includes - {include-localized script.js}
			$current_localized_javascript_dir = $this->javascript_base_dir."/".$this->language;
			$template = preg_replace_callback('/'.$this->tag_ldelim.'include-localized\s+([a-zA-Z0-9\-\.]*\.js)\s*'.$this->tag_rdelim.'/i', function($matches) use ($current_localized_javascript_dir) {
					return SParser::_parseJavascriptInclude($matches, $current_localized_javascript_dir);	
				}, $template);					
				
				
			//Includes - {include father.html}
			$current_template_dir = $this->template_dir;
			$template = preg_replace_callback('/'.$this->tag_ldelim.'include\s+([a-zA-Z0-9\-\.]*)\s*'.$this->tag_rdelim.'/i', function($matches) use ($context, $current_template_dir) {
					return SParser::_parseInclude($matches, $context, $current_template_dir);	
				}, $template);
			
			
				
			//if the cache is enable let's save the view 
			//for further uses
			if($this->useCache){

				$hash = $actual_hash;
					
				if(is_null($hash)){
					$hash = SViews::getArrayHash($context);	
				}
				
				$fileName = $template_name.$hash.".cache";
				
				$fileHandler = fopen($this->cache_dir."/".$fileName,"w");
				fwrite($fileHandler,$template);
				fclose($fileHandler);
				
			}	
				
			return $template;
		}
	}
	
	public function setUseCache($useCache){
		if(is_bool($useCache)){
			$this->useCache = $useCache;
		}
	}
	
	
	public static function getArrayHash($array){
		$str_arr = var_export($array,true);
		return hash("MD5", $str_arr);
	}
	
}

class SParser {
	public static function _parseVar($matches, $context) {
		if (isset($context[$matches[1]])) {
			return $context[$matches[1]];
		} else {
			throw new TemplateException("Variable '{$matches[1]}' is not defined in current context.");
		}
	}
	
	public static function _parseDottedVar($matches, $context) {
		//Order: $foo['bar'], $foo->bar(), $foo->getBar(), $foo->get('bar')
		list ($foo, $bar) = explode('.', $matches[1]);
		if (is_array($context[$foo]) && isset($context[$foo][$bar])) {
			return $context[$foo][$bar];
		} elseif (method_exists($context[$foo], $bar)) {
			return $context[$foo]->$bar();
		} elseif (method_exists($context[$foo], 'get'.ucfirst($bar))) {
			$methodName = 'get'.ucfirst($bar);
			return $context[$foo]->$methodName();
		} elseif (method_exists($context[$foo], 'get')) {
			return $context[$foo]->get($bar);
		} else {
			throw new TemplateException("Variable, method or hash key '{$matches[1]}' is not defined in current context.");
		}
	}	
	
	public static function _parseInclude($matches, $context, $template_dir) {
		$s = new SViews($template_dir);
		return $s->render($matches[1], $context);
		
	}

	public static function _parseJavascriptInclude($matches, $template_dir) {
		$js_path =  $template_dir."/".$matches[1];
		return '<script type="text/javascript" src="'.$js_path.'"></script>';
	}
}

class TemplateException extends RuntimeException { }

?>
