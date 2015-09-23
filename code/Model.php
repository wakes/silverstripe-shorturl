<?php

class ShortURLModel extends DataObject {
	const DefaultRetryMax = 10;             // default number of attempts to regenerate key without a collision

	// maximum number of retries to avoid a generated key collision
	private static $retry_max = self::DefaultRetryMax;

	// stops a key from having it's url updated
    private static $prevent_updates = true;

	// take valid characters for the key from this string.
    private static $valid_chars = 'ABCDEFGHIJKLMNOPQRSTVWXYZabcdefghijklmnopqrstvwxyz0123456789';

	/**
	 * Create a new record for the given url, write it and return it.
	 * @return ShortURLModel
	 */
	public static function generate($url) {
		$model = new self([
			ShortURLModelExtension::url_field_name() => $url
		]);
		$model->write();
		return $model;
	}

	/**
	 * Returns the 'real' url.
	 * @return string
	 */
	public function ShortURLReal() {
		return $this->{ShortURLModelExtension::url_field_name()};
	}

	/**
	 * Returns the key value.
	 * @return string
	 */
	public function ShortURLKey() {
		return $this->{ShortURLModelExtension::key_field_name()};
	}


	/**
	 * If we are new and don't already have a key then assign a new unique key. If we aren't new then optionally
	 * depending on config.prevent_updates prevents updating of key for an existing Key.
	 *
	 * @throws Exception
	 */
	public function onBeforeWrite() {
		if (!$this->isInDB()) {
			$keyFieldName = ShortURLModelExtension::key_field_name();

			if (!$this->ShortURLKey()) {
				$this->{$keyFieldName} = self::unique_key();
			} else {
				// shouldn't happen if we have enough config.retry_max retries
				if (self::key_exists($this->{$keyFieldName})) {
					throw new ShortURLException("Key collision on '$this->$keyFieldName'");
				}
			}
		} elseif ($this->config()->get('prevent_updates')) {
			$url = self::get_url_by_key($this->{$keyFieldName});
			if ($url != $this->URL) {
				throw new ShortURLException("Can't update existing key with a new URL");
			}
		}
		parent::onBeforeWrite();
	}

	/**
	 * Return a ShortURLModel found for a url or null if not found.
	 * @param $url
	 * @return ShortURLModel|null
	 */
	public static function get_for_url($url) {
		return ShortURLModel::get()->filter(ShortURLModelExtension::url_field_name(), $url)->first();

	}

	/**
	 * Return a ShortURLModel found for a Key or null if not found.
	 * @param $key
	 * @return ShortURLModel|null
	 */
    public static function get_by_key($key) {
        return ShortURLModel::get()->filter(ShortURLModelExtension::key_field_name(), $key)->first();
    }

	/**
	 * Return the URL for a key or null.
	 * @param $key
	 * @return string|null
	 */
	public static function get_url_by_key($key) {
		if ($existing = self::get_by_key($key)) {
			return $existing->ShortURLKey();
		}
	}

    /**
     * Build a key from config.valid_chars of self.KeyLength length testing for records
     * already existing with that key and failing after self.DefaultRetryMax attempts.
     *
     * @return string
     * @throws Exception
     */
    public static function unique_key() {
        $key = '';
        $chars = (string)Config::inst()->get(__CLASS__, 'valid_chars');
        $length = strlen($chars);
        $retryCount = 0;
	    $retryMax = (int)Config::inst()->get(__CLASS__, 'retry_max');

        do {
            for ($i = 0; $i < ShortURLModelExtension::key_length(); $i++) {
                $key .= substr($chars, rand(0, $length - 1), 1);
            }
            $exists = self::key_exists($key);

        } while ($exists && (++$retryCount < $retryMax));

        if ($retryCount >= self::DefaultRetryMax) {
            throw new Exception("Tried '$retryCount' times to get a key and failed, you might need to increase your key length");
        }

        return $key;
    }

	/**
	 * Checks if key exists or not for a ShortURLModel.
	 * @param string $key
	 * @return bool
	 */
    public static function key_exists($key) {
        return ShortURLModel::get()->filter('Key', $key)->count() != 0;
    }

}