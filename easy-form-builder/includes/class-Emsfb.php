<?php

if (!defined('ABSPATH')) {
    die("Direct access of plugin files is not allowed.");
}

/**
 * Class Emsfb
 */
class Emsfb {
    public $plugin_path = "";

    public $plugin_url = "";

    /**
     * Emsfb constructor.
     */
    public function __construct() {
        $this->plugin_path = EMSFB_PLUGIN_DIRECTORY;
        $this->plugin_url  = EMSFB_PLUGIN_URL;

        $this->includes();
        $this->init_hooks();
        if(is_admin()==false){
            $this->webhooks();
        }else{
            $this->init_elementor_compatibility_efb();
       }

    }

    /**
     * Initial plugin setup.
     */
    private function init_hooks(): void {
        register_activation_hook(
            EMSFB_PLUGIN_FILE,
            ['\Emsfb\Install', 'install']
        );


        add_action('upgrader_process_complete', [$this, 'plugin_update_completed_efb'], 10, 2);


        add_action('plugins_loaded', [$this, 'check_version_and_upgrade_efb']);

    }


    /**
     * Includes classes and functions.
     */
    public function includes(): void {
        require_once $this->plugin_path . 'includes/class-Emsfb-install.php';

        if (is_admin()) {
            require_once $this->plugin_path . 'includes/admin/class-Emsfb-admin.php';
            require_once $this->plugin_path . 'includes/admin/class-Emsfb-create.php';
            require_once $this->plugin_path . 'includes/admin/class-Emsfb-addon.php';
            if(is_dir(EMSFB_PLUGIN_DIRECTORY."/vendor/smssended")) {

                require_once EMSFB_PLUGIN_DIRECTORY. '/vendor/smssended/class-Emsfb-sms.php';
            }

        }


        require_once $this->plugin_path . 'includes/class-Emsfb-public.php';


    }


    public function webhooks(){

       /* add_action('rest_api_init',  @function(){


              register_rest_route('efb/v1','test/(?P<name>[a-zA-Z0-9_]+)/(?P<id>[a-zA-Z0-9_]+)', [
                  'method'=> 'GET',
                  'callback'=> 'test_fun'
              ]);
          }); */
    }


    public function check_db_change_efb(){
        global $wpdb;
        $test_tabale = $wpdb->prefix . "Emsfb_form";
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $test_tabale ) );
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is properly escaped
		$check_test_table = $wpdb->get_var( $query );
        $table_name = $wpdb->prefix . "emsfb_form";

        if(strlen($check_test_table)>0){
			if ( strcmp($table_name,$check_test_table)!=0) {
                $message =  esc_html__('The Easy Form Builder had Important update and require to deactivate and activate the plugin manually </br> Notice:Please do this act in immediately so forms of your site will available again.','easy-form-builder');
                ?>
                    <div class="notice notice-warning is-dismissible">
                        <p> <?php echo '<b>'.esc_html__('Warning', 'easy-form-builder').':</b> '. wp_kses_post($message); ?> </p>
                    </div>
                <?php
            $this->email_send_efb();
            }
        }
    }

 /**
     * Send email notification to all super admins about database changes
     *
     * @since 3.9.5
     * @return void
     */
    public static function email_send_efb() {
        $message = esc_html__( 'The Easy Form Builder had Important update and require to deactivate and activate the plugin manually </br> Notice: Please do this act immediately so forms of your site will be available again.', 'easy-form-builder' );


        $super_admins = get_super_admins();

        if ( empty( $super_admins ) ) {
            return;
        }


        $recipients = array();

        foreach ( $super_admins as $admin_login ) {
            $user = get_user_by( 'login', $admin_login );

            if ( $user && is_email( $user->user_email ) ) {
                $recipients[] = sanitize_email( $user->user_email );
            }
        }


        if ( empty( $recipients ) ) {
            return;
        }


        $server_name = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : 'yourdomain.com';
        $from_email  = 'no-reply@' . $server_name;
        $from_name   = get_bloginfo( 'name' );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', $from_name, $from_email ),
        );


        $subject = sprintf(

            esc_html__( 'Important Warning from %s', 'easy-form-builder' ),
            get_bloginfo( 'name' )
        );



        wp_mail( $recipients, $subject, wp_kses_post( $message ), $headers );
    }

    /**
     * Initialize Elementor compatibility for all EFB admin pages
     */
    public function init_elementor_compatibility_efb() {

        if (!$this->is_elementor_admin_active_efb()) {
            return;
        }


        if (isset($_GET['page']) && (
            sanitize_key( $_GET['page'] ) === 'Emsfb' ||
            sanitize_key( $_GET['page'] ) === 'Emsfb_create' ||
            sanitize_key( $_GET['page'] ) === 'Emsfb_addon' ||
            sanitize_key( $_GET['page'] ) === 'Emsfb_sms_efb'
        )) {
            add_action('admin_enqueue_scripts', array($this, 'apply_elementor_admin_fixes_efb'), 1);
        }
    }

    /**
     * Apply Elementor admin compatibility fixes to prevent conflicts
     */
    public function apply_elementor_admin_fixes_efb() {

        add_action('admin_footer', array($this, 'elementor_admin_conflict_prevention_efb'));
    }

    /**
     * Check if Elementor is active in admin context
     */
    public function is_elementor_admin_active_efb() {

        if (class_exists('\Elementor\Plugin') || defined('ELEMENTOR_VERSION')) {
            return true;
        }


        if (function_exists('is_plugin_active') && is_plugin_active('elementor/elementor.php')) {
            return true;
        }

        return false;
    }

    /**
     * Add JavaScript to prevent Elementor admin conflicts
     */
    public function elementor_admin_conflict_prevention_efb() {
        $current_page = isset($_GET['page']) ? sanitize_key( $_GET['page'] ) : '';
        ?>
        <script type="text/javascript">

        (function($) {
            'use strict';


            if (typeof window.efb_global_elementor_protection === 'undefined') {
                window.efb_global_elementor_protection = true;

                console.log('EFB Global: Initializing Elementor compatibility layer for <?php echo esc_js($current_page); ?>');


                if (typeof elementorFrontend !== 'undefined') {
                    try {

                        if (!elementorFrontend.tools) {
                            elementorFrontend.tools = {};

                        }
                    } catch (e) {
                        console.log('EFB Global: Prevented Elementor frontend error:', e.message);
                    }
                }


                $(document).ready(function() {

                    $(window).on('error', function(e) {
                        if (e.originalEvent && e.originalEvent.message) {
                            var errorMessage = e.originalEvent.message.toLowerCase();
                            if (errorMessage.includes('dispatchevent') ||
                                errorMessage.includes('elementor') ||
                                errorMessage.includes('tools') ||
                                errorMessage.includes('cannot read properties of undefined')) {
                                if (window.console && window.console.log && typeof window.efb_debug !== 'undefined' && window.efb_debug) {

                                }
                                e.preventDefault();
                                return false;
                            }
                        }
                    });


                    if (window.EventTarget && window.EventTarget.prototype && EventTarget.prototype.dispatchEvent) {
                        var originalDispatchEvent = EventTarget.prototype.dispatchEvent;
                        EventTarget.prototype.dispatchEvent = function(event) {
                            try {
                                return originalDispatchEvent.call(this, event);
                            } catch (e) {
                                if (window.console && typeof window.efb_debug !== 'undefined' && window.efb_debug) {

                                }
                                return false;
                            }
                        };
                    }
                });
            }
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Check version and run upgrade tasks if needed
     *
     * @since 3.9.5
     * @return void
     */
    public function check_version_and_upgrade_efb() {
        $installed_version = get_option('emsfb_version', '0.0.0');
        $current_version = EMSFB_PLUGIN_VERSION;


        if (version_compare($installed_version, $current_version, '<')) {
            $this->run_upgrade_tasks_efb($installed_version, $current_version);
            update_option('emsfb_version', $current_version);
        }
    }

    /**
     * Run upgrade tasks after plugin update
     *
     * @since 3.9.5
     * @param string $old_version Old plugin version
     * @param string $new_version New plugin version
     * @return void
     */
    private function run_upgrade_tasks_efb($old_version, $new_version) {

        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }


        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group('emsfb');
        }


        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_efb_%' OR option_name LIKE '_transient_timeout_efb_%'"
        );


        error_log(sprintf(
            'Easy Form Builder upgraded from %s to %s - All caches cleared',
            $old_version,
            $new_version
        ));
    }

    /**
     * Hook that runs when plugin is updated via WordPress admin
     *
     * @since 3.9.5
     * @param object $upgrader_object Plugin upgrader object
     * @param array $options Update options
     * @return void
     */
    public function plugin_update_completed_efb($upgrader_object, $options) {

        if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
            return;
        }


        $our_plugin = plugin_basename(EMSFB_PLUGIN_FILE);

        if (isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin === $our_plugin) {

                    $this->run_upgrade_tasks_efb(
                        get_option('emsfb_version', '0.0.0'),
                        EMSFB_PLUGIN_VERSION
                    );
                    break;
                }
            }
        }
    }

}
