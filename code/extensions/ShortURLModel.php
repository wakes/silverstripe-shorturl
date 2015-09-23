<?php

/**
 * Adds DB fields to ShortURLModel with a dynamic 'ShortURL' schema depending on config.key_length
 */
class ShortURLModelExtension extends DataExtension {
	const DefaultKeyFieldType = 'Varchar';      // keep length
	const DefaultKeyLength = 5;                    // keep in sync with self.KeyFieldType, may need to increase for large sites
	const KeyFieldName = 'ShortURLKey';
	const URLFieldName = 'ShortURLValue';

	private static $key_field_type = self::DefaultKeyFieldType;

	private static $key_length = self::DefaultKeyLength;


	public function setShortURL($url) {
		$this->owner->{self::url_field_name()} = $url;
	}

	public static function get_extra_config($class, $extension, $args) {
		$config = parent::get_extra_config($class, $extension, $args) ?: [];

		$config += array(
			'db' => [
				self::KeyFieldName => self::key_field_type() . '(' . self::key_length() . ')',
			    self::URLFieldName => 'Text'
			]
		);
		return $config;
	}

	public static function url_field_name() {
		return self::URLFieldName;
	}

	public static function key_field_name() {
		return self::KeyFieldName;
	}

	public static function key_field_type() {
		static $keyFieldType = null;

		return is_null($keyFieldType)
			? $keyFieldType = Config::inst()->get(__CLASS__, 'key_field_type')
				?: self::DefaultKeyFieldType
			: $keyFieldType;
	}

	public static function key_length() {
		static $keyLength = null;

		return is_null($keyLength)
			? $keyLength = Config::inst()->get(__CLASS__, 'key_length')
				?: self::DefaultKeyLength
			: $keyLength;
	}

}