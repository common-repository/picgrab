/*

  A reversible password encryption routine
  Distributed under the GNU General Public Licence

*/
<?php
if (!class_exists('encryption_class')) {
class encryption_class {


    function base64url_encode( $data ){
      return rtrim( strtr( base64_encode( $data ), '+/', '-_'), '=');
    }
    function base64url_decode( $data ){
      return base64_decode( strtr( $data, '-_', '+/') . str_repeat('=', 3 - ( 3 + strlen( $data )) % 4 ));
    }


    // ****************************************************************************
    // class constructor
    // ****************************************************************************
    function __construct ()  {

    } 

    // ****************************************************************************

    function decrypt($ciphertext){
      $key = "AbsakjIDjjosdx1234";
    //  $c = base64_decode($ciphertext);
      $c = $this->base64url_decode($ciphertext);
      $ivlen = openssl_cipher_iv_length($cipher="aes-128-cbc");
      $iv = substr($c, 0, $ivlen);
      $hmac = substr($c, $ivlen, $sha2len=32);
      $ciphertext_raw = substr($c, $ivlen+$sha2len);
      $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
      
      return $original_plaintext;

    }
    function encrypt( $plaintext ) {
      $key = "AbsakjIDjjosdx1234";


      $ivlen = openssl_cipher_iv_length($cipher="aes-128-cbc");

      $iv = openssl_random_pseudo_bytes($ivlen);
      $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
      $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
      //$ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );
      $ciphertext = $this->base64url_encode( $iv.$hmac.$ciphertext_raw );

      return $ciphertext;

    }


} // end encryption_class
// ****************************************************************************
}
?>
