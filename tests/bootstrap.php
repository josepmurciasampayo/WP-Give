<?php
/**
 * Give Unit Tests Bootstrap
 *
 * @since 1.3.2
 */
class Give_Unit_Tests_Bootstrap {

	/** @var \Give_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib	is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/**
	 * Setup the unit testing environment
	 *
	 * @since 1.3.2
	 */
	public function __construct() {

		ini_set( 'display_errors', 'on' );
		error_reporting( E_ALL );

		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
		$_SERVER['SERVER_NAME']     = '';
		$PHP_SELF                   = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

		define( 'GIVE_USE_PHP_SESSIONS', false );
		
		$this->tests_dir 	= dirname( __FILE__ );
		$this->plugin_dir	= dirname( $this->tests_dir );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';

		// load test function so tests_add_filter() is available
		require_once( $this->wp_tests_dir . '/includes/functions.php' );

		// load Give
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_give' ) );

		// install Give
		tests_add_filter( 'setup_theme', array( $this, 'install_give' ) );

		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		// load Give testing framework
		$this->includes();
	}

	/**
	 * Load Give
	 *
	 * @since 1.3.2
	 */
	public function load_give() {
		require_once( $this->plugin_dir . '/give.php' );
	}

	/**
	 * Install Give after the test environment and Give have been loaded.
	 *
	 * @since 1.3.2
	 */
	public function install_give() {

		// clean existing install first
		define( 'WP_UNINSTALL_PLUGIN', true );
		include( $this->plugin_dir . '/uninstall.php' );

		echo "Installing Give..." . PHP_EOL;

		give_install();

		// reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374
		$GLOBALS['wp_roles']->reinit();

		global $give_options;

		$give_options = get_option( 'give_settings' );
	}

	/**
	 * Load Give-specific test cases
	 *
	 * @since 1.3.2
	 */
	public function includes() {

		//Helpers
		require_once( $this->tests_dir . '/framework/helpers/shims.php' );
		require_once( $this->tests_dir . '/framework/helpers/class-helper-form.php' );
		require_once( $this->tests_dir . '/framework/helpers/class-helper-payment.php' );
	}

	/**
	 * Get the single class instance.
	 *
	 * @since 2.2
	 * @return Give_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Give_Unit_Tests_Bootstrap::instance();