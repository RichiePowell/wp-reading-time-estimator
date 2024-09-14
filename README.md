# Reading Time Estimator Plugin

A simple WordPress plugin that calculates and displays the estimated reading time for posts. The reading time is based on the word count of the post and a configurable reading speed. It offers several customization options, including label customization, time format, and the ability to exclude short posts.

<img width="1239" alt="Screenshot 2024-09-11 at 4 09 39" src="https://github.com/user-attachments/assets/45fdde56-0243-4005-8984-b1dc532b5f50">

## Features

- Automatically calculates reading time based on the post content.
- Allows users to set the reading speed (words per minute).
- Customize the label for the reading time (e.g., "Estimated Reading Time:").
- Choose between full time format (e.g., "5 minutes") or shorthand (e.g., "5m").
- Option to toggle automatic insertion of the reading time into posts.
- Exclude short posts from displaying reading time and define the word count for what is considered a short post.
- Shortcode support for manually adding the reading time anywhere in the post: `[reading_time]`.

## Installation

1. Download the plugin and upload the folder `reading-time-estimator` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings > Reading Time Estimator** to customize the plugin options.

## Usage

### Automatic Insertion

By default, the reading time is automatically inserted at the beginning of each post. You can customize this in the plugin's settings.

### Shortcode

To manually insert the reading time in a post or a template, use the following shortcode:

```php
[reading_time]
```

### Settings

You can access the plugin settings under Settings > Reading Time Estimator. The following options are available:

- Reading Speed (Words per Minute): Customize the reading speed used to calculate the reading time.
- Reading Time Label: Define the text that appears before the reading time (e.g., “Estimated Reading Time:”).
- Time Format: Choose between the full format (e.g., “5 minutes”) or shorthand (e.g., “5m”).
- Automatic Insertion: Toggle whether the reading time should be automatically inserted at the start of posts.
- Exclude Short Posts: Toggle whether short posts should display the reading time.
- Short Post Word Count: Set the word count threshold for what counts as a short post.

## Development

### Adding New Features

To add new features or modify the plugin, edit the files in the `/wp-content/plugins/reading-time-estimator/` directory.

Make sure to keep the following important files updated:

- _reading-time-estimator.php_: Main plugin file, contains hooks and logic.
- _settings.php_: Contains the settings logic and page.

## Requirements

- WordPress 5.0 or higher.
- PHP 7.0 or higher.

## License

This plugin is licensed under the GPL-2.0+ license. You are free to use, modify, and distribute it as long as you retain the same license.
