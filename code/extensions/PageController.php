<?php

class ShortURLPageControllerExtension extends DataExtension {
	const DefaultKeyParam = 'ShortURLKey';
	const DefaultAction = 'shorturl';
	const HandlerName = 'shortURLHandler';
	const MethodRedirect = 'redirect';
	const MethodDirect = 'direct';

	private static $key_param = self::DefaultKeyParam;

	/** @var string url parameter action, can be an existing one if added higher in route chain */
	private static $action = self::DefaultAction;

	/** @var bool use Controller.redirect */
	private static $method = self::MethodRedirect;

	/** @var int redirect code to send */
	private static $redirect_code = 302;

	/**
	 * Add allowed_actions and url_handlers based on config.key_param and config.action.
	 * @param $class
	 * @param $extension
	 * @param $args
	 * @return array
	 */
	public static function get_extra_config($class, $extension, $args) {
		$config = parent::get_extra_config($class, $extension, $args) ?: [];

		$action = self::action();
		$keyParam = self::key_param();

		$config += [
			'allowed_actions' => [
				self::HandlerName
			],
		    'url_handlers' => [
				"$action/\$$keyParam!" => self::HandlerName
		    ]
		];
		return $config;
	}

	/**
	 * @return Page_Controller
	 */
	protected function owner() {
		return $this->owner;
	}

	/**
	 * Return handler name which is hardwired to the function name
	 * @return string
	 */
	public static function handler_name() {
		return self::HandlerName;
	}

	/**
	 * @return string
	 */
	public static function key_param() {
		return Config::inst()->get(__CLASS__, 'key_param');
	}

	/**
	 * @return string
	 */
	public static function action() {
		return Config::inst()->get(__CLASS__, 'action');
	}

	/**
	 * Checks if method is 'redirect' and if so returns config.redirect_code otherwise false
	 * @return int|bool
	 */
	public static function use_method_redirect_code() {
		return self::method() == self::MethodRedirect
			? Config::inst()->get(__CLASS__, 'redirect_code')
			: false;
	}

	/**
	 * Check if method is 'direct'
	 * @return bool
	 */
	public static function use_method_direct() {
		return self::method() == self::MethodDirect;
	}

	/**
	 * Returns the output method ('direct' or 'redirect')
	 * @return string
	 */
	public static function method() {
		return (string)Config::inst()->get(__CLASS__, 'method');
	}

	/**
	 * Feed the action back to the extended controller to do something with it via 'config.HandlerName' method and
	 * return the first not-null response from the extend call. If config.HandlerName is not defined on the extended
	 * controller then do a redirect or direct from matching key to url in database.
	 *
	 */
	public function shortURLHandler(SS_HTTPRequest $request) {
		$handlerName = self::handler_name();

		$key = $request->param(self::key_param());

		if ($key) {
			if ($this->owner()->hasMethod($handlerName)) {

				return $this->owner()->{$handlerName}($key, $request);

			} else {
				/** @var ShortURLModel $shortURL */
				if ($shortURL = ShortURLModel::get_by_key($key)) {

					$realURL = $shortURL->ShortURLReal();

					if ($code = self::use_method_redirect_code()) {

						return $this->owner()->redirect($realURL, $code);

					} elseif (self::use_method_direct()) {

						Director::direct($realURL, DataModel::inst());
						return;

					} else {
						throw new ShortURLException("No output method configured");
					}
				}
			}
		}
		$this->owner()->httpError(404);
	}
}