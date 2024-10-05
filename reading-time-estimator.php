<?php
/**
 * Plugin Name: Reading Time Estimator
 * Description: Estimates and displays reading time for posts.
 * Version: 1.0
 * Author: Richard Powell
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Format the reading time into a readable string.
 *
 * @param array $reading_time An array containing hours, minutes, and seconds.
 * @param string $format The format to use: 'full' or 'short'.
 * @return string The formatted reading time.
 */
function rtestimator_format_reading_time( $reading_time, $format = 'full' ) {
    $formatted_time = '';

    if ( $format === 'short' ) {
        // Short format (e.g., 1h 5m 30s)
        if ( $reading_time['hours'] > 0 ) {
            $formatted_time .= $reading_time['hours'] . 'h ';
        }
        if ( $reading_time['minutes'] > 0 ) {
            $formatted_time .= $reading_time['minutes'] . 'm ';
        }
        if ( $reading_time['seconds'] > 0 ) {
            $formatted_time .= $reading_time['seconds'] . 's';
        }
    } else {
        // Full format (e.g., 1 hour 5 minutes 30 seconds)
        if ( $reading_time['hours'] > 0 ) {
            $formatted_time .= $reading_time['hours'] . ( $reading_time['hours'] > 1 ? ' hours ' : ' hour ' );
        }
        if ( $reading_time['minutes'] > 0 ) {
            $formatted_time .= $reading_time['minutes'] . ( $reading_time['minutes'] > 1 ? ' minutes ' : ' minute ' );
        }
        if ( $reading_time['seconds'] > 0 ) {
            $formatted_time .= $reading_time['seconds'] . ( $reading_time['seconds'] > 1 ? ' seconds' : ' second' );
        }
    }

    // Trim any trailing spaces
    return trim( $formatted_time );
}

/**
 * Calculate the reading time based on word count.
 *
 * @param mixed $content The content to calculate reading time for.
 * @return array The calculated reading time in hours, minutes, and seconds.
 */
function rtestimator_calculate_reading_time( $content ) {
    $word_count = str_word_count( wp_strip_all_tags( $content ) );
    $reading_speed = get_option( 'rtestimator_reading_speed', 200 ); // Use saved option or default 200
    $total_seconds = ceil( ($word_count / $reading_speed) * 60 ); // Total time in seconds

    $hours = floor( $total_seconds / 3600 );
    $minutes = floor( ( $total_seconds % 3600 ) / 60 );
    $seconds = $total_seconds % 60;

    return [
        'hours'   => $hours,
        'minutes' => $minutes,
        'seconds' => $seconds,
    ];
}

/**
 * Render the reading time for a post.
 *
 * @param mixed $content The content to calculate and display reading time for.
 * @return string The formatted reading time.
 */
function rtestimator_render_reading_time( $content ) {
    $reading_time = rtestimator_calculate_reading_time( $content );
    $label = esc_html( get_option( 'rtestimator_reading_time_label', 'Estimated Reading Time:' ) );
    $format = get_option( 'rtestimator_reading_time_format', 'full' );

    $formatted_time = rtestimator_format_reading_time( $reading_time, $format );

    return '<p><strong>' . $label . '</strong> ' . $formatted_time . '</p>';
}

/**
 * Display reading time in post content automatically.
 *
 * @param mixed $content The content of the post.
 * @return mixed The content with reading time appended.
 */
function rtestimator_display_reading_time( $content ) {
    global $post;

    // Check if the content has the [reading_time] shortcode, show it if so
    if ( has_shortcode( $post->post_content, 'reading_time' ) ) {
        return rtestimator_reading_time_shortcode() . $content;
    }

    // Check if automatic insertion is enabled
    if ( is_single() && get_option( 'rtestimator_auto_insert', true ) ) {
        $word_count = str_word_count( wp_strip_all_tags( $content ) );
        $exclude_short_posts = get_option( 'rtestimator_exclude_short_posts', false );
        $short_post_word_count = get_option( 'rtestimator_short_post_word_count', 300 );

        // If short posts are excluded and the word count is below the threshold, skip the reading time
        if ( $exclude_short_posts && $word_count < $short_post_word_count ) {
            return $content;
        }

        // Prepend the reading time to the content
        return rtestimator_render_reading_time( $content ) . $content;
    }

    // Return the original content if automatic insertion is disabled
    return $content;
}
add_filter( 'the_content', 'rtestimator_display_reading_time' );

/**
 * Shortcode handler for displaying reading time.
 *
 * @return string The formatted reading time.
 */
function rtestimator_reading_time_shortcode() {
    global $post;

    if ( isset( $post->post_content ) ) {
        return rtestimator_render_reading_time( $post->post_content );
    }

    return '';
}
add_shortcode( 'reading_time', 'rtestimator_reading_time_shortcode' );

/**
 * Register settings.
 *
 * @return void
 */
function rtestimator_register_settings() {
    // Add default values (optional)
    add_option( 'rtestimator_reading_speed', 200 );
    add_option( 'rtestimator_reading_time_label', 'Estimated Reading Time:' );
    add_option( 'rtestimator_reading_time_format', 'full' );
    add_option( 'rtestimator_auto_insert', true );
    add_option( 'rtestimator_exclude_short_posts', false );
    add_option( 'rtestimator_short_post_word_count', 300 );

    // Register each setting with the correct options group
    register_setting( 'rtestimator_options_group', 'rtestimator_reading_speed' );
    register_setting( 'rtestimator_options_group', 'rtestimator_reading_time_label' );
    register_setting( 'rtestimator_options_group', 'rtestimator_reading_time_format' );
    register_setting( 'rtestimator_options_group', 'rtestimator_auto_insert' );
    register_setting( 'rtestimator_options_group', 'rtestimator_exclude_short_posts' );
    register_setting( 'rtestimator_options_group', 'rtestimator_short_post_word_count' );
}
add_action( 'admin_init', 'rtestimator_register_settings' );

/**
 * Register options page.
 *
 * @return void
 */
function rtestimator_register_options_page() {
    add_options_page( 'Reading Time Estimator', 'Reading Time', 'manage_options', 'reading-time-estimator', 'rtestimator_options_page' );
}
add_action( 'admin_menu', 'rtestimator_register_options_page' );

/**
 * Display the settings page for Reading Time Estimator.
 *
 * @return void
 */
function rtestimator_options_page() {
    // Ensure nonce field for security
    ?>
    <div class="rtestimator-grid-container">
        <!-- Main Settings Form -->
        <div class="rtestimator-settings-box">
            <h2>Reading Time Estimator Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields( 'rtestimator_options_group' ); ?>
                <?php wp_nonce_field( 'rtestimator_save_settings', 'rtestimator_nonce' ); ?>
                
                <label for="rtestimator_reading_speed">Reading speed (words per minute):</label>
                <input type="number" id="rtestimator_reading_speed" name="rtestimator_reading_speed" value="<?php echo esc_attr( get_option( 'rtestimator_reading_speed' ) ); ?>" />
                
                <label for="rtestimator_reading_time_label">Label for reading time:</label>
                <input type="text" id="rtestimator_reading_time_label" name="rtestimator_reading_time_label" value="<?php echo esc_html( get_option( 'rtestimator_reading_time_label', 'Estimated Reading Time:' ) ); ?>" />
                
                <label for="rtestimator_reading_time_format">Time format:</label>
                <select id="rtestimator_reading_time_format" name="rtestimator_reading_time_format">
                    <option value="full" <?php selected( get_option( 'rtestimator_reading_time_format' ), 'full' ); ?>>Full (e.g., 5 minutes)</option>
                    <option value="short" <?php selected( get_option( 'rtestimator_reading_time_format' ), 'short' ); ?>>Shorthand (e.g., 5m)</option>
                </select>
                
                <label for="rtestimator_auto_insert">
                    <input type="checkbox" id="rtestimator_auto_insert" name="rtestimator_auto_insert" value="1" <?php checked( 1, get_option( 'rtestimator_auto_insert', true ), true ); ?> />
                    Automatically insert in posts
                </label>

                <label for="rtestimator_exclude_short_posts">
                    <input type="checkbox" id="rtestimator_exclude_short_posts" name="rtestimator_exclude_short_posts" value="1" <?php checked( 1, get_option( 'rtestimator_exclude_short_posts', false ), true ); ?> />
                    Exclude short posts
                </label>

                <label for="rtestimator_short_post_word_count">Word count threshold for short posts:</label>
                <input type="number" id="rtestimator_short_post_word_count" name="rtestimator_short_post_word_count" value="<?php echo esc_attr( get_option( 'rtestimator_short_post_word_count', 300 ) ); ?>" />
                
                <?php submit_button(); ?>
            </form>
        </div>

        <!-- Shortcode Information Section -->
        <div class="rtestimator-shortcode-info">
            <h3>Shortcode Information</h3>
            <p>You can manually insert the reading time into your post by using the following shortcode:</p>
            <pre><code>[reading_time]</code></pre>
            <p>Simply place this shortcode anywhere in your post content, and the reading time will be displayed at that location.</p>
            <p>If you want more control over where the reading time appears, disable automatic insertion in the settings above.</p>
        </div>
    </div>
    <?php
}

/**
 * Enqueue custom styles for the plugin settings page.
 */
function rtestimator_enqueue_admin_styles() {
    // Only load the styles on the plugin's settings page
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'reading-time-estimator' ) {
        wp_enqueue_style( 'rtestimator-admin-style', plugins_url( 'admin-style.css', __FILE__ ), array(), '1.0.0' );
    }
}
add_action( 'admin_enqueue_scripts', 'rtestimator_enqueue_admin_styles' );

/**
 * Validate the form and verify nonce before saving settings.
 */
function rtestimator_save_settings() {
    // First, unslash the $_POST data
    $post_data = wp_unslash( $_POST );

    // Nonce verification
    if ( ! isset( $post_data['rtestimator_nonce'] ) || ! wp_verify_nonce( $post_data['rtestimator_nonce'], 'rtestimator_save_settings' ) ) {
        wp_die( 'Invalid nonce verification. Settings not saved.' );
    }

    // Sanitize and save the reading speed setting
    if ( isset( $post_data['rtestimator_reading_speed'] ) ) {
        $reading_speed = absint( $post_data['rtestimator_reading_speed'] );
        update_option( 'rtestimator_reading_speed', $reading_speed );
    }

    // Sanitize and save the reading time label setting
    if ( isset( $post_data['rtestimator_reading_time_label'] ) ) {
        $reading_time_label = sanitize_text_field( $post_data['rtestimator_reading_time_label'] );
        update_option( 'rtestimator_reading_time_label', $reading_time_label );
    }

    // Sanitize and save the time format setting
    if ( isset( $post_data['rtestimator_reading_time_format'] ) ) {
        $reading_time_format = sanitize_text_field( $post_data['rtestimator_reading_time_format'] );
        update_option( 'rtestimator_reading_time_format', $reading_time_format );
    }

    // Sanitize and save the auto insert option
    $auto_insert = isset( $post_data['rtestimator_auto_insert'] ) ? 1 : 0;
    update_option( 'rtestimator_auto_insert', $auto_insert );

    // Sanitize and save the exclude short posts option
    $exclude_short_posts = isset( $post_data['rtestimator_exclude_short_posts'] ) ? 1 : 0;
    update_option( 'rtestimator_exclude_short_posts', $exclude_short_posts );

    // Sanitize and save the short post word count
    if ( isset( $post_data['rtestimator_short_post_word_count'] ) ) {
        $short_post_word_count = absint( $post_data['rtestimator_short_post_word_count'] );
        update_option( 'rtestimator_short_post_word_count', $short_post_word_count );
    }
}