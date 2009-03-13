<?php
require_once 'OAuth.php';

class omb_OAuthDataStore extends OAuthDataStore {/*{{{*/
  public function revoke_token($token_key) {
    throw new Exception();
  }

  public function authorize_token($token_key) {
    throw new Exception();
  }
}/*}}}*/
?>
