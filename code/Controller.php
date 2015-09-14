<?php
class ShortURLController extends Controller {

    private static $allowed_actions = array(
        'redirect' => true
    );
    private static $url_handlers = array(
        '$ShortURL!' => 'redirect'
    );

    /**
     * Read ShortURL key from request and redirect to the full URL from the matching
     * CheckfrontShortenedURL record.
     *
     * @param SS_HTTPRequest $request
     *
     * @return SS_HTTPResponse
     */
    public function redirect(SS_HTTPRequest $request) {
        if ($shortURL = $request->param('ShortURL')) {
            if ($fullURL = ShortURLModel::get_url_by_key($shortURL)) {
                return parent::redirect($fullURL);
            }
        }
        $this->httpError("Bad URL");
    }

}