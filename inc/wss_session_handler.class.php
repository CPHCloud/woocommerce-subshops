<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handle data for the current customers session.
 * Implements the WC_Session abstract class
 *
 * This is a modified version of the session handler
 * in WooCommerce. It saves the session data in an array
 * with the key set to the ID of current subshop. If no subshop
 * is present the key will be 'main_shop'.
 *
 * @class 		wss_session_handler
 * @category	Class
 */
class wss_session_handler extends WC_Session {

	/** cookie name */
	public $_cookie;

	/** session due to expire timestamp */
	private $_session_expiring;

	/** session expiration timestamp */
	private $_session_expiration;

	/**
	 * Constructor for the session class.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->_cookie = 'wp_woocommerce_session_' . COOKIEHASH;

		if ( $cookie = $this->get_session_cookie() ) {
			$this->_customer_id        = $cookie[0];
			$this->_session_expiration = $cookie[1];
			$this->_session_expiring   = $cookie[2];

			// Update session if its close to expiring
			if ( time() > $this->_session_expiring ) {
				$this->set_session_expiration();
				update_option( '_wc_session_expires_' . $this->_customer_id, $this->_session_expiration );
			}

		} else {
			$this->set_session_expiration();
			$this->_customer_id = $this->generate_customer_id();
		}

		$this->_data = $this->get_session_data();

    	// Actions
    	add_action( 'woocommerce_set_cart_cookies', array( $this, 'set_customer_session_cookie' ), 10 );
    	add_action( 'woocommerce_cleanup_sessions', array( $this, 'cleanup_sessions' ), 10 );
    	add_action( 'shutdown', array( $this, 'save_data' ), 20 );
    }

    /**
     * Sets the session cookie on-demand (usually after adding an item to the cart).
     *
     * Since the cookie name (as of 2.1) is prepended with wp, cache systems like batcache will not cache pages when set.
     *
     * Warning: Cookies will only be set if this is called before the headers are sent.
     */
    public function set_customer_session_cookie( $set ) {
    	if ( $set ) {
	    	// Set/renew our cookie
	    	$to_hash      = $this->_customer_id . $this->_session_expiration;
	    	$cookie_hash  = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
	    	$cookie_value = $this->_customer_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;

	    	// Set the cookie
	    	wc_setcookie( $this->_cookie, $cookie_value, $this->_session_expiration, apply_filters( 'wc_session_use_secure_cookie', false ) );
	    }
    }

    /**
     * set_session_expiration function.
     *
     * @access public
     * @return void
     */
    public function set_session_expiration() {
	    $this->_session_expiring    = time() + intval( apply_filters( 'wc_session_expiring', 60 * 60 * 47 ) ); // 47 Hours
		$this->_session_expiration  = time() + intval( apply_filters( 'wc_session_expiration', 60 * 60 * 48 ) ); // 48 Hours
    }

	/**
	 * generate_customer_id function.
	 *
	 * @access public
	 * @return mixed
	 */
	public function generate_customer_id() {
		if ( is_user_logged_in() )
			return get_current_user_id();
		else
			return wp_generate_password( 32, false );
	}

	/**
	 * get_session_cookie function.
	 *
	 * @access public
	 * @return mixed
	 */
	public function get_session_cookie() {
		if ( empty( $_COOKIE[ $this->_cookie ] ) )
			return false;

		list( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) = explode( '||', $_COOKIE[ $this->_cookie ] );

		// Validate hash
		$to_hash = $customer_id . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( $hash != $cookie_hash )
			return false;

		return array( $customer_id, $session_expiration, $session_expiring, $cookie_hash );
	}

	/**
	 * get_session_data function.
	 *
	 * @access public
	 * @return array
	 */
	public function get_session_data() {
		$data = get_option( '_wc_session_' . $this->_customer_id, array());
		if($shop = wss::get_current_shop()){
			$return = $data[$shop->ID];
		}
		else{
			$return = $data['main_shop'];
		}

		if(!$return){
			$return = array();
		}

		return $return;
	}

    /**
     * save_data function.
     *
     * @access public
     * @return void
     */
    public function save_data() {
    	// Dirty if something changed - prevents saving nothing new
    	if ( $this->_dirty ) {

	    	$session_option 		= '_wc_session_' . $this->_customer_id;
    		$session_expiry_option 	= '_wc_session_expires_' . $this->_customer_id;
    		
    		$shop = wss::get_current_shop();
	    	if ( false === get_option( $session_option ) ) {
	    		$data = array();
	    		if($shop){
	    			$data[$shop->ID] = $this->_data;
	    		}
	    		else{
	    			$data['main_shop'] = $this->_data;
	    		}
	    		add_option( $session_option, $data, '', 'no');
		    	add_option( $session_expiry_option, $this->_session_expiration, '', 'no' );
	    	} else {
	    		$data = get_option($session_option, array());
	    		if($shop){
	    			$data[$shop->ID] = $this->_data;
	    		}
	    		else{
	    			$data['main_shop'] = $this->_data;
	    		}
		    	update_option( $session_option, $data);
	    	}
	    }
    }

    /**
	 * cleanup_sessions function.
	 *
	 * @access public
	 * @return void
	 */
	public function cleanup_sessions() {
		global $wpdb;

		if ( ! defined( 'WP_SETUP_CONFIG' ) && ! defined( 'WP_INSTALLING' ) ) {
			$now                = time();
			$expired_sessions   = array();
			$wc_session_expires = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '_wc_session_expires_%'" );

			foreach ( $wc_session_expires as $wc_session_expire ) {
				if ( $now > intval( $wc_session_expire->option_value ) ) {
					$session_id         = substr( $wc_session_expire->option_name, 20 );
					$expired_sessions[] = $wc_session_expire->option_name;  // Expires key
					$expired_sessions[] = "_wc_session_$session_id"; // Session key
				}
			}

			if ( ! empty( $expired_sessions ) ) {
				$option_names = implode( "','", $expired_sessions );
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name IN ('$option_names')" );
			}
		}
	}
}