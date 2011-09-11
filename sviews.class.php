<?php
/**
 * SViews GIT version
 * Requires PHP 5.3+
 * Copyright (c) 2011 Simone Lusenti
 * 
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class SViews {
	private $template_dir="templates";
	private $cache_dir="cache";
	private $ldelim="{{";
	private $rdelim="}}";
	private $tag_rdelim="}";
	private $tag_ldelim="{";
	
	public function __construct() {
		$this->template_dir=dirname(__FILE__).DIRECTORY_SEPARATOR.$this->template_dir;
		$this->cache_dir=dirname(__FILE__).DIRECTORY_SEPARATOR.$this->cache_dir;
	}
	
	public function render($template_name, array $context=array()) {
		if (empty($context)) echo '[warning: empty context provided]';
		
		$filename = $this->template_dir.DIRECTORY_SEPARATOR.$template_name;
		if (!file_exists($filename)) {
			throw new TemplateException("Template file does not exists: $filename");
			
		} else {
			$template = file_get_contents($filename);
			
			//Variables - {{name}}
			$template = preg_replace_callback('/'.$this->ldelim.'\s*(\w*)\s*'.$this->rdelim.'/i', function($matches) use ($context) {
					return SParser::_parseVar($matches, $context);	
				}, $template);
			
			//Includes - {include father.html}
			$template = preg_replace_callback('/'.$this->tag_ldelim.'include\s+([a-zA-Z0-9\-\.]*)\s*'.$this->tag_rdelim.'/i', function($matches) use ($context) {
					return SParser::_parseInclude($matches, $context);	
				}, $template);
			
			
			return $template;
		}
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
	
	public static function _parseInclude($matches, $context) {
		$s = new SViews();
		return $s->render($matches[1], $context);
	}
}

class TemplateException extends RuntimeException { }

?>
