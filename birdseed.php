<?php

/**
 *
 * @link              https://www.birdseed.io
 * @since             2.0.0
 * @package           BirdSeed
 *
 * @wordpress-plugin
 * Plugin Name:       BirdSeed
 * Plugin URI:        https://www.birdseed.io
 * Description:       Adds the BirdSeed widget embed code to your Wordpress website.
 * Version:           2.2.0
 * Author:            BirdSeed
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       birdseed
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

const PLUGIN_NAME_VERSION = '2.2.0';

add_action("admin_menu", "setup");

function setup() {
    // Seeding the menu
    add_menu_page(
        __("Birdseed", "birdseed"),
        __("Birdseed", "birdseed"),
        "administrator", __FILE__,
        "birdseed_plugin_settings_page"
    );
    add_action("admin_notices", "register_birdseed_notice");
}

function birdseed_plugin_settings_page() {
    if (current_user_can("administrator")) {
        if (!empty($_GET["birdseed_token"])) {
            update_option("birdseed_token", sanitize_text_field($_GET["birdseed_token"]));

            // Clear WP Rocket Cache if needed
            if (function_exists("rocket_clean_domain")) {
                rocket_clean_domain();
            }

            // Clear WP Super Cache if needed
            if (function_exists("wp_cache_clean_cache")) {
                global $file_prefix;
                wp_cache_clean_cache($file_prefix, true);
            }
        }
    }

    $birdseed_token = get_option("birdseed_token");
    $callback = "http" . (($_SERVER["SERVER_PORT"] == 443) ? "s://" : "://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
    $callback = remove_query_arg('_wpnonce', $callback);
    $callback = remove_query_arg('birdseed_token', $callback);
    $callback = base64_encode(wp_nonce_url($callback));
    wp_enqueue_script("birdseed", esc_url_raw("https://app.birdseed.io/assets/bs_renderer_script.js?birdseed_token=" . $birdseed_token . "&cms=wordpress&callback=" . $callback), array(), "", true);
    include_once(plugin_dir_path( __FILE__ ) . "/views/admin.php");
}

function register_birdseed_notice() {
    $birdseed_token = get_option("birdseed_token");
    // Do not display if we don't have the token OR if we are on the birdseed page
    if (empty($birdseed_token) && $_GET["page"] != plugin_basename(__FILE__)) {
        $admin_url = admin_url("admin.php?page=".plugin_basename(__FILE__));
        ?>
        <div class="notice notice-warning is-dismissible notice-birdseed">
            <p>
                <img src="<?php echo plugins_url("/assets/icon-128x128.png", __FILE__); ?>" height="16" />
                &nbsp;
                <?php
                echo sprintf(
                    esc_html(__('The Birdseed plugin isn’t connected right now. To display the Birdseed widget on your WordPress site, %1$sconnect the plugin now%2$s. The configuration only takes 1 minute!', "birdseed") ),
                    "<a href='$admin_url'>",
                    "</a>"
                );
                ?>
            </p>
        </div>
<?php
    }
}

add_action("wp_footer", "birdseed_footer", 1);
function birdseed_footer() {
    $birdseed_token = get_option("birdseed_token");
    if (empty($birdseed_token)) {
        return '';
    }
    include_once(plugin_dir_path(__FILE__) . "/views/public.php");
}