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
 * Add reading time to post content.
 *
 * @param mixed $content
 * @return float
 */
function rte_calculate_reading_time( $content ) {
  $word_count = str_word_count( strip_tags( $content ) );
  $reading_speed = get_option( 'rte_reading_speed', 200 ); // Use saved option or default 200
  $minutes = ceil( $word_count / $reading_speed );
  return $minutes;
}

/**
 * Display reading time in post content.
 *
 * @param mixed $content
 * @return mixed
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

      // Calculate reading time
      $reading_time = rte_calculate_reading_time( $content );
      $label = esc_html( get_option( 'rte_reading_time_label', 'Estimated Reading Time:' ) );
      $format = get_option( 'rte_reading_time_format', 'full' );

      // Format the reading time display based on user preference
      if ( $format === 'short' ) {
          $reading_time_display = $reading_time . 'm'; // Short format (e.g., 5m)
      } else {
          $unit = $reading_time > 1 ? 'minutes' : 'minute';
          $reading_time_display = $reading_time . ' ' . $unit; // Full format (e.g., 5 minutes)
      }

      // Prepend the reading time to the content
      return '<p><strong>' . $label . '</strong> ' . $reading_time_display . '</p>' . $content;
  }

  // Return the original content if automatic insertion is disabled
  return $content;
}
add_filter( 'the_content', 'rte_display_reading_time' );

/**
 * Register settings.
 *
 * @return void
 */
function rte_register_settings() {
  add_option( 'rte_reading_speed', 200 );
  add_option( 'rte_reading_time_label', 'Estimated Reading Time:' );
  add_option( 'rte_reading_time_format', 'full' );
  add_option( 'rte_auto_insert', true );
  add_option( 'rte_exclude_short_posts', false ); // New setting to exclude short posts
  add_option( 'rte_short_post_word_count', 300 ); // New setting for short post word count

  register_setting( 'rte_options_group', 'rte_reading_speed' );
  register_setting( 'rte_options_group', 'rte_reading_time_label' );
  register_setting( 'rte_reading_time_format' );
  register_setting( 'rte_auto_insert' );
  register_setting( 'rte_exclude_short_posts' );
  register_setting( 'rte_short_post_word_count' );
}

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
	<div class="rte-settings-page">
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