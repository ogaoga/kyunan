<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Vendor', 'twitter', array('search'=>ROOT.DS.'vendors'));

if ( ! class_exists('OAuthConsumer') ) {
	App::import('Vendor', 'oauth', array('file' => 'OAuth'.DS.'OAuth.php', 'search'=>ROOT.DS.'vendors'));
}

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package       cake
 * @subpackage    cake.app
 */
class AppController extends Controller {

	var $components = array('Session');
	var $auth = null;
	var $accessToken = null;
  
  var $uses = array('User');

	function beforeFilter() {
		$this->accessToken = $this->Session->read('accessToken');
		if ( $this->accessToken ) {
			$this->auth = $this->Session->read('twitter_auth');
			if ( ! $this->auth ) {
				$twitterApi = new TwitterApi(TWITTER_CONSUMER_KEY,
																		 TWITTER_CONSUMER_SECRET,
																		 $this->accessToken,
																		 $_SERVER['SERVER_NAME']==HOST_LOCALHOST);
				$account = $twitterApi->verifyCredentials();
				if ( $account ) {
					$this->auth = $account;
					$this->Session->write('twitter_auth', $this->auth);
          // save 
          $this->_saveUser($account);
				}
				else {
					$this->auth = null;
				}
			}
		}
		else {
		}
    $this->set('auth', $this->auth);
	}

  private function _saveUser($account) {
    $data = $this->User->findById((int)$account->id_str);
    if ( $data ) {
      // user already exists
      $this->User->id = (int)$account->id_str;
      $data['User']['screen_name'] = $account->screen_name;
      $data['User']['oauth_token_key'] = $this->accessToken->key;
      $data['User']['oauth_token_secret'] = $this->accessToken->secret;
      $data['User']['modified'] = null;
    }
    else {
      // new user
      $this->User->create();
      $this->User->id = (int)$account->id_str;
      $data = array('User'=>array('id'=>(int)$account->id_str,
                                  'screen_name'=>$account->screen_name,
                                  'oauth_token_key'=>$this->accessToken->key,
                                  'oauth_token_secret'=>$this->accessToken->secret,
                                  'settings'=>null
                                  ));
      $data['User']['enabled'] = 1;
      // follow user by icotile
      if ( ! $account->protected ) {
        $this->_followByIcotile($this->User->id);
      }
    }
    // save
    $this->User->save($data);
  }

  private function _followByIcotile($user_id) {
    $icotile = $this->User->findByScreenName('icotile');
    $token = new OAuthToken(ICOTILE_OAUTH_TOKEN_KEY,
                            ICOTILE_OAUTH_TOKEN_SECRET);
    $tw = new TwitterApi(TWITTER_CONSUMER_KEY,
												 TWITTER_CONSUMER_SECRET,
												 $token);
    $result = $tw->followWithUserId($user_id);
    if ( TwitterApi::isError($result) ) {
      $this->log($result);
    }
  }
}
