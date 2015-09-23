<?php

class ShortURLModel extends DataObject {
	// maximum number of retries to avoid a generated key collision
	private static $retry_max = self::DefaultRetryMax;

	// stops a key from having it's url updated
    private static $prevent_updates = true;

	// take valid characters for the key from this string.
    private static $valid_chars = 'ABCDEFGHIJKLMNOPQRSTVWXYZabcdefghijklmnopqrstvwxyz0123456789';


	/**
	 * Return a ShortURLModel returned by Key or null if not found.
	 * @param $key
	 * @return ShortURLModel|null
	 */
    public static function get_url_by_key($key) {
        if ($existing = ShortURLModel::get()->filter('Key', $key)->first()) {
            return $existing->URL;
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
        $chars = (string)static::config()->get('valid_chars');
        $length = strlen($chars);
        $retryCount = 0;
	    $retryMax = (int)static::config()->get('retry_max');

        do {
            for ($i = 0; $i < self::KeyLength; $i++) {
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

    /**
     * If we are new and don't already have a key then assign a new unique key. If we aren't new then optionally
     * depending on config.prevent_updates prevents updating of key for an existing Key.
     *
     * @throws Exception
     */
    public function onBeforeWrite() {
        if (!$this->isInDB()) {
            if (!$this->Key) {
                $this->Key = self::unique_key();
            } else {
	            // shouldn't happen if we have enough config.retry_max retries
                if (self::key_exists($this->Key)) {
                    throw new ShortURLException("Key collision on '$this->Key'");
                }
            }
        } elseif ($this->config()->get('prevent_updates')) {
            $url = self::get_url_by_key($this->Key);
            if ($url != $this->URL) {
                throw new ShortURLException("Can't update existing key with a new URL");
            }
        }
        parent::onBeforeWrite();
    }
}