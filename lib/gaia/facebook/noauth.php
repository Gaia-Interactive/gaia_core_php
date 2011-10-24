<?php
namespace Gaia\Facebook;

class NoAuth extends \BaseFacebook {
  public function __construct(){ parent::__construct( array('appId'=>'', 'secret'=>'')); }
  protected function setPersistentData($key, $value){ return false; }
  protected function getPersistentData($key, $default = false){ return false; }
  protected function clearPersistentData($key){ return false; }
  protected function clearAllPersistentData(){ return false; }
  protected function _oauthRequest($url, $params) {
    foreach ($params as $key => $value) {
      if (!is_string($value)) $params[$key] = json_encode($value);
    }
    return $this->makeRequest($url, $params);
    }
}
