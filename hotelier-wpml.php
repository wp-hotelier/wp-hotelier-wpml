<?php
/**
 * Plugin Name:       Easy WP Hotelier Multilingual
 * Plugin URI:        http://wphotelier.com/
 * Description:       Run a multilingual website with Easy WP Hotelier and WPML.
 * Version:           1.3.0
 * Author:            Easy WP Hotelier
 * Author URI:        http://wphotelier.com/
 * Requires at least: 4.0
 * Tested up to:      5.2
 * Text Domain:       wp-hotelier-wpml
 * Domain Path:       languages
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Hotelier_WPML' ) ) :

/**
 * Main Hotelier_WPML Class
 */
final class Hotelier_WPML {

	/**
	 * @var string
	 */
	public $version = '1.3.0';

	/**
	 * @var Hotelier_WPML The single instance of the class
	 */
	private static $_instance = null;

	/**
	 * Main Hotelier_WPML Instance
	 *
	 * Insures that only one instance of Hotelier_WPML exists in memory at any one time.
	 *
	 * @static
	 * @return Hotelier_WPML - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wp-hotelier-wpml' ), '0.9.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wp-hotelier-wpml' ), '0.9.0' );
	}

	/**
	 * Hotelier_WPML Constructor.
	 */
	public function __construct() {
		$this->setup_constants();
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
	}

	/**
	 * Setup plugin constants
	 *
	 * @access private
	 * @return void
	 */
	private function setup_constants() {
		$upload_dir = wp_upload_dir();

		// Plugin version
		if ( ! defined( 'HTL_WPML_VERSION' ) ) {
			define( 'HTL_WPML_VERSION', $this->version );
		}

		// Plugin Folder Path
		if ( ! defined( 'HTL_WPML_PLUGIN_DIR' ) ) {
			define( 'HTL_WPML_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Folder URL
		if ( ! defined( 'HTL_WPML_PLUGIN_URL' ) ) {
			define( 'HTL_WPML_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File
		if ( ! defined( 'HTL_WPML_PLUGIN_FILE' ) ) {
			define( 'HTL_WPML_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Basename
		if ( ! defined( 'HTL_WPML_PLUGIN_BASENAME' ) ) {
			define( 'HTL_WPML_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

	/**
	 * Hook into actions and filters
	 */
	public function init() {
		if ( defined( 'HTL_VERSION' ) && version_compare( HTL_VERSION, '2.0.0', '<' ) ) {
			// Add notice for old Easy WP Hotelier versions
			add_action( 'admin_notices', array( $this, 'show_notice_for_old_version' ) );
		}

		// Check if WPML and Hotelier are installed
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) || ! defined( 'HTL_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'error_no_plugins' ) );

			return;
		}

		// Set up localisation
		$this->load_textdomain();

		// Load admin scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		// Add translate pages in settings
		add_filter( 'hotelier_settings_print_select', array( $this, 'add_page_settings' ), 10, 3 );

		// Cart functions
		add_filter( 'hotelier_add_to_cart_room_id', array( $this, 'add_to_cart' ), 10, 2 );
		add_action( 'hotelier_get_cart_item_from_session', array( $this, 'translate_cart_contents' ), 10 );

		// Conditional tag for listing page
		add_filter( 'hotelier_is_listing', array( $this, 'is_listing' ) );

		// Conditional tag for booking page
		add_filter( 'hotelier_is_booking', array( $this, 'is_booking' ) );

		// Load rooms in default language in manual reservation
		add_action( 'hotelier_admin_add_new_reservation_before_rooms_query', array( $this, 'before_rooms_add_new_reservation_query' ), 10 );
		add_action( 'hotelier_admin_add_new_reservation_after_rooms_query', array( $this, 'after_rooms_add_new_reservation_query' ), 10 );

		// Get room IDs in default language (used in the calendar page)
		add_filter( 'hotelier_get_room_ids_for_reservations', array( $this, 'get_room_ids_for_reservations' ) );

		// Delete room IDs transient during room trash and room save
		add_action( 'wp_trash_post', array( $this, 'trash_room' ) );
		add_action( 'publish_room', array( $this, 'new_room' ) );

		// Add WPML fields in room search widget
		add_action( 'hotelier_after_widget_room_search_fields', array( $this, 'add_widget_fields' ) );

		// Check if the room is available (listing page)
		add_filter( 'hotelier_room_is_available', array( $this, 'is_available' ), 10, 4 );

		// When calculating the dates to disable on the datepicker, pass the original room ID
		add_action( 'hotelier_get_room_id_for_unavailable_days_on_datepicker', array( $this, 'get_room_id_for_unavailable_days_on_datepicker' ) );
		add_action( 'hotelier_get_room_ids_for_unavailable_days_on_datepicker', array( $this, 'get_room_ids_for_unavailable_days_on_datepicker' ) );
	}

	/**
	 * Show info when WPML and/or Hotelier are not installed .
	 */
	public function error_no_plugins() {
		$message = __( 'Easy WP Hotelier Multilingual plugin is enabled but not effective. It requires %s and %s plugins in order to work.', 'wp-hotelier-wpml' );

		echo '<div class="error"><p>' . sprintf( $message, '<a href="http://wpml.org/">WPML</a>', '<a href="https://wphotelier.com/">Easy WP Hotelier</a>' ) . '</p></div>';
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @return void
	 */
	public function load_textdomain() {
		// Set filter for plugin's languages directory
		$hotelier_wpml_lang_dir = dirname( plugin_basename( HTL_WPML_PLUGIN_FILE ) ) . '/languages/';
		$hotelier_wpml_lang_dir = apply_filters( 'hotelier_wpml_languages_directory', $hotelier_wpml_lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-hotelier-wpml' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'wp-hotelier-wpml', $locale );

		// Setup paths to current locale file
		$mofile_local  = $hotelier_wpml_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/wp-hotelier-wpml/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/wp-hotelier-wpml folder
			load_textdomain( 'wp-hotelier-wpml', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/wp-hotelier-wpml/languages/ folder
			load_textdomain( 'wp-hotelier-wpml', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'wp-hotelier-wpml', false, $hotelier_wpml_lang_dir );
		}
	}

	/**
	 * Enqueue styles and scripts
	 */
	public function admin_scripts() {
		$screen = get_current_screen();

		if ( $screen->id == 'toplevel_page_hotelier-settings' ) {
			wp_enqueue_style( 'hotelier-wpml', HTL_WPML_PLUGIN_URL . 'assets/css/hotelier-wpml.css', array(), HTL_WPML_VERSION );
		}
	}

	/**
	 * Get pages of default language
	 */
	public function get_pages_default_language() {
		$all_pages = array( '' => '' ); // Blank option

		if ( ( ! isset( $_GET[ 'page' ] ) || 'hotelier-settings' != $_GET[ 'page' ] ) ) {
			return $all_pages;
		}

		// Switch to the default language
		global $sitepress;
		$sitepress->switch_lang( $sitepress->get_default_language() );

		$pages = get_pages();

		if ( $pages ) {
			foreach ( $pages as $page ) {
				$all_pages[ $page->ID ] = $page->post_title;
			}
		}

		// Switch back to the current language
		$sitepress->switch_lang( ICL_LANGUAGE_CODE );

		return $all_pages;
	}

	/**
	 * Get language name from code
	 */
	public function get_language_name( $code ) {
		global $sitepress;

		$details       = $sitepress->get_language_details( $code );
		$language_name = $details[ 'display_name' ];

		return $language_name;
	}

	/**
	 * Display translated pages in settings
	 */
	public function add_page_settings( $html, $args, $value ) {
		if ( isset( $args[ 'id' ] ) && ( 'listing_page' == $args[ 'id' ] || 'booking_page' == $args[ 'id' ] || 'terms_page' == $args[ 'id' ] ) ) {
			$html = $this->print_wpml_select( $args, $value );
		}

		return $html;
	}

	// Print WPML select field
	public function print_wpml_select( $args, $value ) {
		global $sitepress;

		$options      = $this->get_pages_default_language();
		$default_lang = $sitepress->get_default_language();
		$size         = ( isset( $args[ 'size' ] ) && ! is_null( $args[ 'size' ] ) ) ? 'htl-ui-input--' . $args[ 'size' ] : '';

		ob_start();
		?>

		<div class="htl-ui-setting htl-ui-setting--select htl-ui-setting--<?php echo esc_attr( $args[ 'id' ] ); ?>">
			<div class="hotelier-wpml-lang-row">
				<span class="hotelier-wpml-lang-label">[ <?php echo $this->get_language_name( $default_lang ); ?> ]</span>

				<select class="<?php echo esc_attr( $size ); ?> htl-ui-input htl-ui-input--select" id="hotelier_settings[<?php echo esc_attr( $args[ 'id' ] ); ?>]" name="hotelier_settings[<?php echo esc_attr( $args[ 'id' ] ); ?>]">
					<?php foreach ( $args[ 'options' ] as $option => $name ) : ?>
						<?php $selected = selected( $option, $value, false ); ?>

						<option value="<?php echo esc_attr( $option ); ?>" <?php echo $selected ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>

				</select>
			</div>

			<?php
			$get_page_translations = $sitepress->get_element_trid( $value, 'post_page' );
			$translations          = $sitepress->get_element_translations( $get_page_translations, 'post_page', false, true );
			$active_languages      = $sitepress->get_active_languages();
			?>

			<?php foreach ( $active_languages as $code => $lang ) : ?>

				<?php
				if ( $code == $default_lang ) {
					continue;
				}
				?>

				<div class="hotelier-wpml-lang-row">

					<span class="hotelier-wpml-lang-label">[ <?php echo $this->get_language_name( $code ); ?> ]</span>

					<?php if ( is_array( $translations ) && isset( $translations[ $code ] ) ) : ?>
						<select class="<?php echo esc_attr( $size ); ?> htl-ui-input htl-ui-input--select">
							<option><?php echo esc_html( $translations[ $lang[ 'code' ] ]->post_title ); ?></option>
						</select>
					<?php else : ?>
						<span class="hotelier-wpml-lang-error"><?php esc_html_e( 'Translation not available', 'wp-hotelier-wpml' ); ?></span>
					<?php endif; ?>

				</div>

			<?php endforeach; ?>

			<?php if ( $args[ 'desc' ] ) : ?>
				<?php
				if ( 'listing_page' == $args[ 'id' ] || 'booking_page' == $args[ 'id' ] ) {
					$args[ 'desc' ] .= ' ' . esc_html__( 'You need to insert the shortcode also in the translated pages.', 'wp-hotelier-wpml' );
				}
				?>
				<label class="htl-ui-label htl-ui-label--text htl-ui-setting__description htl-ui-setting__description--select htl-ui-setting__description--<?php echo esc_attr( $args[ 'id' ] ); ?>"><?php echo wp_kses_post( $args[ 'desc' ] ); ?></label>
			<?php endif; ?>
		</div>

		<?php
		return ob_get_clean();
	}

	// Add to the cart the original room instead of the translated one
	public function add_to_cart( $id, $room ) {
		global $sitepress;

		$default_lang = $sitepress->get_default_language();
		$curr_lang    = ICL_LANGUAGE_CODE;

		if ( $curr_lang != $default_lang ) {
			$id = icl_object_id( get_the_ID(), 'room', false, $default_lang );
		}

		return $id;
	}

	// Translate cart contents
	public function translate_cart_contents( $item ) {
		global $sitepress;

		$default_lang = $sitepress->get_default_language();
		$curr_lang    = ICL_LANGUAGE_CODE;

		if ( $curr_lang != $default_lang ) {
			$room_id = apply_filters( 'translate_object_id', $item[ 'room_id' ], 'room', true );
			$item[ 'data' ]->post->post_title = get_the_title( $room_id );

			if ( $item[ 'rate_name' ] ) {
				$_room               = htl_get_room( $room_id );
				$item[ 'rate_name' ] = $_room->get_rate_name( $item[ 'rate_id' ] );
			}
		}

		return $item;
	}

	/**
	 * Add translated listing pages to the is_listing() conditional tag
	 */
	public function is_listing() {
		$listing_page_id = htl_get_page_id( 'listing' );

		if ( is_numeric( $listing_page_id ) ) {
			global $sitepress;

			$default_lang = $sitepress->get_default_language();
			$curr_lang    = ICL_LANGUAGE_CODE;

			if ( $curr_lang != $default_lang ) {

				$get_page_translations = $sitepress->get_element_trid( $listing_page_id, 'post_page' );
				$translations = $sitepress->get_element_translations( $get_page_translations, 'post_page', false, true );

				foreach ( $translations as $code => $translation ) {
					if ( get_the_ID() == $translation->element_id ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Add translated booking pages to the is_booking() conditional tag
	 */
	public function is_booking() {
		$booking_page_id = htl_get_page_id( 'booking' );

		if ( is_numeric( $booking_page_id ) ) {
			global $sitepress;

			$default_lang = $sitepress->get_default_language();
			$curr_lang    = ICL_LANGUAGE_CODE;

			if ( $curr_lang != $default_lang ) {
				$get_page_translations = $sitepress->get_element_trid( $booking_page_id, 'post_page' );
				$translations = $sitepress->get_element_translations( $get_page_translations, 'post_page', false, true );

				foreach ( $translations as $code => $translation ) {
					if ( get_the_ID() == $translation->element_id ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Switch to the default language before to run the query
	 */
	public function before_rooms_add_new_reservation_query( $args ) {
		global $sitepress;
		$sitepress->switch_lang( $sitepress->get_default_language() );
	}

	/**
	 * Switch back to the current language after the query
	 */
	public function after_rooms_add_new_reservation_query( $args ) {
		global $sitepress;
		$sitepress->switch_lang( ICL_LANGUAGE_CODE );
	}

	// Get room IDs in default language
	public function get_room_ids_for_reservations() {
		// Load from cache
		$room_ids = get_transient( 'hotelier_wpml_room_ids_default_lang' );

		// Valid cache found
		if ( false !== $room_ids ) {
			return $room_ids;
		}

		// Switch to the default language
		global $sitepress;
		$sitepress->switch_lang( $sitepress->get_default_language() );

		$rooms = get_posts( array(
			'post_type'           => 'room',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => -1,
			'fields'              => 'ids',
			'suppress_filters'    => 0,
			'meta_query'          => array(
				array(
					'key'     => '_stock_rooms',
					'value'   => 0,
					'type'    => 'numeric',
					'compare' => '>',
				),
			),
		) );

		// Switch back to the current language
		$sitepress->switch_lang( ICL_LANGUAGE_CODE );

		set_transient( 'hotelier_wpml_room_ids_default_lang', $rooms, DAY_IN_SECONDS * 30 );

		return $rooms;
	}

	/**
	 * Check if the original room is available (listing page)
	 */
	public function is_available( $is_available, $room_id, $checkin, $checkout ) {
		// First, check if this is a translation
		global $sitepress;

		$default_lang = $sitepress->get_default_language();
		$curr_lang    = ICL_LANGUAGE_CODE;

		if ( $curr_lang != $default_lang ) {
			$id = icl_object_id( $room_id, 'room', false, $default_lang );

			if ( $id ) {
				$_room = htl_get_room( $id );

				// Unhook this function so it doesn't loop infinitely
				remove_filter( 'hotelier_room_is_available', array( $this, 'is_available' ), 10, 4 );

				if ( ! $_room->is_available( $checkin, $checkout ) ) {
					$is_available = false;
				}

				// Re-hook this function
				add_filter( 'hotelier_room_is_available', array( $this, 'is_available' ), 10, 4 );
			}
		}

		return $is_available;
	}

	/**
	 * When calculating the dates to disable on the datepicker, pass the original room ID
	 */
	public function get_room_id_for_unavailable_days_on_datepicker( $room_id ) {
		global $sitepress;

		$default_lang = $sitepress->get_default_language();
		$curr_lang    = ICL_LANGUAGE_CODE;

		if ( $curr_lang != $default_lang ) {
			$room_id = icl_object_id( $room_id, 'room', false, $default_lang );

			if ( $room_id  ) {
				return $room_id;
			}
		}

		return $room_id;
	}

	/**
	 * When calculating the dates to disable on the datepicker,
	 * remove trasnalations from the room IDs array
	 */
	public function get_room_ids_for_unavailable_days_on_datepicker( $room_id ) {
		return $this->get_room_ids_for_reservations();
	}

	/**
	 * Delete transient when during room trash
	 */
	public function trash_room( $post_id ) {
		if ( get_post_type() === 'room' ) {

			delete_transient( 'hotelier_wpml_room_ids_default_lang' );
		}
	}

	/**
	 * Delete transient when during room save
	 */
	public function new_room() {
		delete_transient( 'hotelier_wpml_room_ids_default_lang' );
	}

	/**
	 * Add WPML fields in room search widget
	 */
	public function add_widget_fields() {
		do_action( 'wpml_add_language_form_field' );
	}

	/**
	 * Show notice for old Easy WP Hotelier versions.
	 */
	public function show_notice_for_old_version() {
		echo '<div class="error"><p>' . sprintf( wp_kses( __( 'This version of <strong>Easy WP Hotelier Multilingual</strong> requires at least <strong>Easy WP Hotelier 2.0.0</strong> to work correctly. Please <a href="%s">update Easy WP Hotelier</a> before to continue. An old version of Easy WP Hotelier may cause some issues.', 'wp-hotelier-wpml' ), array( 'strong' => array(), 'a' => array( 'href' => array() ) ) ), admin_url( 'plugins.php?plugin_status=upgrade' ) ) . '</p></div>';
	}
}

endif;

/**
 * Returns the main instance of HTL_WPML to prevent the need to use globals.
 *
 * @return Hotelier_WPML
 */
function HTL_WPML() {
	return Hotelier_WPML::instance();
}

// Get HTL_WPML Running
HTL_WPML();
