<?php

/**
 * Piwik - Open source web analytics
 * SiteSearch Plugin
 * Database helper class
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @author Timo Besenreuther, EZdesign.de
 *
 * @category Piwik_Plugins
 * @package Piwik_SiteSearch
 */

class Piwik_SiteSearch_Db {
	
	/** Original bindings for sql queries */
	private static $bindings;
	
	/** Modified bindings for sql queries */
	private static $newBindings;
	
	/** @see Piwik_Query */
	public static function query($sql, $bindings=array()) {
		return self::rebindQuery('Piwik_Query', $sql, $bindings);
	}
	
	/** @see Piwik_FetchRow */
	public static function fetchRow($sql, $bindings=array()) {
		return self::rebindQuery('Piwik_FetchRow', $sql, $bindings);
	}
	
	/** @see Piwik_FetchAll */
	public static function fetchAll($sql, $bindings=array()) {
		return self::rebindQuery('Piwik_FetchAll', $sql, $bindings);
	}
	
	/** @see Piwik_FetchOne */
	public static function fetchOne($sql, $bindings=array()) {
		return self::rebindQuery('Piwik_FetchOne', $sql, $bindings);
	}
	
	/** Change query bindings from named bindings to ? bindings */
	private static function rebindQuery($function, &$sql, &$bindings) {
		self::$bindings = $bindings;
		self::$newBindings = array();
		
		$sql = preg_replace_callback('/:[a-z0-9]*/i',
				array('Piwik_SiteSearch_Db', 'replaceBinding'), $sql);
				
		return call_user_func($function, $sql, self::$newBindings);
	}
	
	/** Callback for preg_replace on the query */
	public static function replaceBinding(&$matches) {
		$name = $matches[0];
		
		$newBinding = false;
		if (isset(self::$bindings[':'.$name])) {
			$newBinding = self::$bindings[':'.$name];
		} else if (isset(self::$bindings[$name])) {
			$newBinding = self::$bindings[$name];
		}
		if ($newBinding === false) {
			throw new Exception('Invalid binding name "'.$name.'"');
		}
		
		self::$newBindings[] = $newBinding;
		return '?';
	}
	
}