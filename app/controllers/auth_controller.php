<?php 

define('TWITTER_OAUTH_REQUEST_URL', 'http://twitter.com/oauth/request_token');
define('TWITTER_OAUTH_AUTH_URL', 'http://twitter.com/oauth/authorize');
define('TWITTER_OAUTH_ACCESS_URL', 'http://twitter.com/oauth/access_token');

App::import('Vendor', 'twitter');

class AuthController extends AppController {
	var $uses = array();
	var $name = 'Auth';
	var $components = array('OauthConsumer');

	function beforeFilter() {
		if ( $this->params['action'] === 'logout'
				 || $this->params['action'] === 'oauth' ) {
			$this->set('auth', null);
		}
		else {
			parent::beforeFilter();
		}
	}

	/**
	 *
	 */
	public function logout() {
		$this->Session->destroy();
		$this->redirect('/');
	}

	/**
	 *
	 */
	public function oauth($redirectPath = "") {
		// clear access token
		$this->Session->write('accessToken', null);
		$this->Session->write('authRedirectPath', $redirectPath);
		// get request token
		$requestToken
			= $this->OauthConsumer->getRequestToken('Twitter',
																							TWITTER_OAUTH_REQUEST_URL);
		// save request token to sesson
		$this->Session->write('requestToken', $requestToken);
		// redirect
		if ( $requestToken ) {
			$this->redirect(TWITTER_OAUTH_AUTH_URL.'?oauth_token='.$requestToken->key);
		}
		else {
			$this->redirect('/');
		}
	}

	public function callback() {
		// read request token from session
		$requestToken
			= $this->Session->read('requestToken');
		// get access token from service
		$accessToken
			= $this->OauthConsumer->getAccessToken('Twitter',
																						 TWITTER_OAUTH_ACCESS_URL,
																						 $requestToken);
		// write it to session
		$this->Session->write('accessToken', $accessToken);
		// redirect to top
		$this->redirect('/'.$this->Session->read('authRedirectPath'));
	}
}

?>
