<?php
/**
 * Plugin Name: All-in-One WP Migration Remote Access Extension
 * Plugin URI: https://apposto.pl/
 * Description: An extension that allows you to remotely download the latest backup after providing an authorization key.
 * Author: Adrian Grabowski Apposto
 * Author URI: https://apposto.pl/
 * Version: 1.0.0
 * Text Domain: all-in-one-wp-migration-remote-access-extension
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

// Check SSL Mode
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) ) {
	$_SERVER['HTTPS'] = 'on';
}

add_action('admin_menu', 'allinone_apposto', 50);

function allinone_apposto() {
    add_submenu_page(
        'ai1wm_export',
        'Remote access',
        'Remote access',
        'export',
        'ai1wm_remote',
        'show_remote_settings_page'
    );
}

function show_remote_settings_page() {
    ?>
    <div class="wrap">
        <h1>Remote access</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('apposto_settings_group');
            do_settings_sections('ai1wmle_apposto');
            submit_button();
            ?>
        </form>

        <?php if(get_option('apposto_remote_token') && trim(get_option('apposto_remote_token')) != "") { ?>
            <h2>Request example</h2>
            <p>The extension allows you to download the latest backup using an HTTP request. To make the request, you need to pass an access token.</p>

            <div class="wrap">
                <pre style="background: white; padding: 10px; border: 1px solid #eee;">wget '<?php echo esc_url(get_site_url()); ?>/wp-admin/admin-post.php?action=remote_backup&token=<?php echo get_option('apposto_remote_token');?>' --content-disposition</pre>
            </div>
        <?php } ?>
    </div>
    <?php
}

add_action('admin_init', 'apposto_register_settings');

function apposto_register_settings() {
    register_setting('apposto_settings_group', 'apposto_remote_token');

    add_settings_section(
        'apposto_main_section',
        'Remote access settings',
        null,
        'ai1wmle_apposto'
    );

    add_settings_field(
        'apposto_remote_token',
        'Access Token',
        'apposto_remote_url_callback',
        'ai1wmle_apposto',
        'apposto_main_section'
    );

    if ( get_option( 'apposto_remote_token' ) === false || get_option( 'apposto_remote_token' ) == "" ) {
        update_option('apposto_remote_token', bin2hex(random_bytes(32)));
    }
}

function apposto_remote_url_callback() {
    $value = esc_attr(get_option('apposto_remote_token'));
    echo '<input type="text" name="apposto_remote_token" value="' . $value . '" class="regular-text">';
}

add_action('admin_post_nopriv_remote_backup', 'apposto_remote_entry');

function apposto_remote_entry() {
    $required_token = get_option('apposto_remote_token');

    if(!$required_token || trim($required_token) == "" ) {
        status_header(500);
        header("Content-type: application/json");
        echo json_encode(['err' => 'Security code not configured']);
        exit;
    }

    $url_token = isset($_GET['token']) ? $_GET['token'] : '';
    if ($url_token !== $required_token) {
        status_header(401);
        header("Content-type: application/json");
        echo json_encode(['err' => 'Invalid security token']);
        exit;
    }

    $location = "wp-content/ai1wm-backups";
    $dir = ABSPATH.$location;
    $extension = 'wpress';

    $files = scandir($dir);
    $latest_file = null;
    $latest_time = 0;

    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === $extension) {
            $file_path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_file($file_path)) {
                $file_time = filemtime($file_path);
                if ($file_time > $latest_time) {
                    $latest_time = $file_time;
                    $latest_file = $file_path;
                }
            }
        }
    }

    if($latest_file) {
        header("Location: ". esc_url(get_site_url())."/". $location. "/" . basename($latest_file));
        exit;
    }

    status_header(404);
    header("Content-type: application/json");
    echo json_encode(['err' => 'No backup found']);
    exit;
}
