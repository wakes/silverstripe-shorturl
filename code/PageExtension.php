<?php

class ShortURLPageExtension extends DataExtension {
	public function ShortURLKey() {
		$shortURL = new ShortURLModel();
		$shortURL->write();
		return $shortURL->Key;
	}
}