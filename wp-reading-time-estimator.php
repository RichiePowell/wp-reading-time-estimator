<?php
/**
 * Plugin Name: WP Reading Time Estimator
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
function rte_format_reading_time( $reading_time, $format = 'full' ) {
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
function rte_calculate_reading_time( $content ) {
	$word_count = str_word_count( strip_tags( $content ) );
	$reading_speed = get_option( 'rte_reading_speed', 200 ); // Use saved option or default 200
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
function rte_render_reading_time( $content ) {
	$reading_time = rte_calculate_reading_time( $content );
	$label = esc_html( get_option( 'rte_reading_time_label', 'Estimated Reading Time:' ) );
	$format = get_option( 'rte_reading_time_format', 'full' );

	$formatted_time = rte_format_reading_time( $reading_time, $format );

	return '<p><strong>' . $label . '</strong> ' . $formatted_time . '</p>';
}

/**
 * Display reading time in post content automatically.
 *
 * @param mixed $content The content of the post.
 * @return mixed The content with reading time appended.
 */
function rte_display_reading_time( $content ) {
	global $post;

	// Check if the content has the [reading_time] shortcode, show it if so
	if ( has_shortcode( $post->post_content, 'reading_time' ) ) {
		return rte_reading_time_shortcode() . $content;
	}

	// Check if automatic insertion is enabled
	if ( is_single() && get_option( 'rte_auto_insert', true ) ) {
		$word_count = str_word_count( strip_tags( $content ) );
		$exclude_short_posts = get_option( 'rte_exclude_short_posts', false );
		$short_post_word_count = get_option( 'rte_short_post_word_count', 300 );

		// If short posts are excluded and the word count is below the threshold, skip the reading time
		if ( $exclude_short_posts && $word_count < $short_post_word_count ) {
			return $content;
		}

		// Prepend the reading time to the content
		return rte_render_reading_time( $content ) . $content;
	}

	// Return the original content if automatic insertion is disabled
	return $content;
}
add_filter( 'the_content', 'rte_display_reading_time' );

/**
 * Shortcode handler for displaying reading time.
 *
 * @return string The formatted reading time.
 */
function rte_reading_time_shortcode() {
	global $post;

	if ( isset( $post->post_content ) ) {
		return rte_render_reading_time( $post->post_content );
	}

	return '';
}
add_shortcode( 'reading_time', 'rte_reading_time_shortcode' );

/**
 * Register settings.
 *
 * @return void
 */
function rte_register_settings() {
	// Add default values (optional)
	add_option( 'rte_reading_speed', 200 );
	add_option( 'rte_reading_time_label', 'Estimated Reading Time:' );
	add_option( 'rte_reading_time_format', 'full' );
	add_option( 'rte_auto_insert', true );
	add_option( 'rte_exclude_short_posts', false );
	add_option( 'rte_short_post_word_count', 300 );

	// Register each setting with the correct options group
	register_setting( 'rte_options_group', 'rte_reading_speed' );
	register_setting( 'rte_options_group', 'rte_reading_time_label' );
	register_setting( 'rte_options_group', 'rte_reading_time_format' );
	register_setting( 'rte_options_group', 'rte_auto_insert' );
	register_setting( 'rte_options_group', 'rte_exclude_short_posts' );
	register_setting( 'rte_options_group', 'rte_short_post_word_count' );
}
add_action( 'admin_init', 'rte_register_settings' );

/**
 * Register options page.
 *
 * @return void
 */
function rte_register_options_page() {
  add_options_page( 'Reading Time Estimator', 'Reading Time', 'manage_options', 'reading-time-estimator', 'rte_options_page' );
}
add_action( 'admin_menu', 'rte_register_options_page' );

/**
 * Display reading time in post content.
 *
 * @return void
 */
function rte_options_page() {
	$reading_speed = esc_attr( get_option( 'rte_reading_speed' ) );
	$reading_time_label = esc_attr( get_option( 'rte_reading_time_label', 'Estimated reading time:' ) );
	$reading_time_format = esc_attr( get_option( 'rte_reading_time_format' ) );
	$auto_insert = get_option( 'rte_auto_insert', true );
	$exclude_short_posts = get_option( 'rte_exclude_short_posts', false );
	$short_post_word_count = esc_attr( get_option( 'rte_short_post_word_count', 300 ) );
	?>
	<div class="rte-grid-container">
		<!-- Main Settings Form -->
		<div class="rte-settings-box">
			<h2>Reading Time Estimator Settings</h2>
			<form method="post" action="options.php">
				<label for="rte_reading_speed">Reading speed (words per minute):</label>
				<input type="number" id="rte_reading_speed" name="rte_reading_speed" value="<?php echo $reading_speed; ?>" />
				
				<label for="rte_reading_time_label">Label for reading time:</label>
				<input type="text" id="rte_reading_time_label" name="rte_reading_time_label" value="<?php echo $reading_time_label; ?>" />
				
				<label for="rte_reading_time_format">Time format:</label>
				<select id="rte_reading_time_format" name="rte_reading_time_format">
					<option value="full" <?php selected( $reading_time_format, 'full' ); ?>>Full (e.g., 5 minutes)</option>
					<option value="short" <?php selected( $reading_time_format, 'short' ); ?>>Shorthand (e.g., 5m)</option>
				</select>
				
				<label for="rte_auto_insert">
					<input type="checkbox" id="rte_auto_insert" name="rte_auto_insert" value="1" <?php checked( 1, $auto_insert, true ); ?> />
					Automatically insert in posts
				</label>

				<label for="rte_exclude_short_posts">
					<input type="checkbox" id="rte_exclude_short_posts" name="rte_exclude_short_posts" value="1" <?php checked( 1, $exclude_short_posts, true ); ?> />
					Exclude short posts
				</label>

				<label for="rte_short_post_word_count">Word count threshold for short posts:</label>
				<input type="number" id="rte_short_post_word_count" name="rte_short_post_word_count" value="<?php echo $short_post_word_count; ?>" />
				
				<?php settings_fields( 'rte_options_group' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>

		<!-- Shortcode Information Section -->
		<div class="rte-shortcode-info">
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
function rte_enqueue_admin_styles() {
	// Only load the styles on the plugin's settings page
	if ( isset( $_GET['page'] ) && $_GET['page'] === 'reading-time-estimator' ) {
			wp_enqueue_style( 'rte-admin-style', plugins_url( 'admin-style.css', __FILE__ ) );
	}
}
add_action( 'admin_enqueue_scripts', 'rte_enqueue_admin_styles' );
