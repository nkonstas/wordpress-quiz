<?php
/**
 * Interactive Quiz
 * 
 * NOTICE: This software is free for both personal and commercial use. However, in accordance
 * with the GPL 3.0 license, any modifications or enhancements made to this software must be
 * shared and submitted to the repository. Please review the LICENSE file for more details.
 *
 * @package           KDQuiz
 * @wordpress-plugin
 * Plugin Name:       Interactive Quiz
 * Plugin URI:        https://github.com/nkonstas/wordpress-quiz
 * Description:       Allows you to create a simple interactive quiz on any page or post
 * Version:           1.1.0
 * Requires at least: 5.4
 * Requires PHP:      7.2
 * Author:            Nikos Konstas
 * Author URI:        https://twitter.com/nkonstas
 * License:           GPL v3 or later
 * License URI:       https://github.com/nkonstas/wordpress-quiz/blob/main/LICENSE
 * Text Domain:       kd-quiz
 */

namespace KDQuiz;

// Stop execution early when someone hits the file directly.
if (!defined('WPINC')) {
    die;
}

/**
 * Prevent fatals when an optional include goes missing in production.
 */
function kdquiz_safe_include($file) {
    if (!@include_once($file)) {
        // Handle the error, e.g., log it or notify the admin
        // For example, writing to a log file or using error_log()
        error_log("Failed to include: " . $file);
    }
}

kdquiz_safe_include( __DIR__ . '/includes/kd-quiz-shared.php' );
kdquiz_safe_include( __DIR__ . '/includes/kd-quiz-frontend.php' );
kdquiz_safe_include( __DIR__ . '/includes/kd-quiz-ajax.php' );

add_action('plugins_loaded', function () {
    // Only load admin tooling for people who can actually use it.
    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
        kdquiz_safe_include( __DIR__ . '/includes/kd-quiz-settings.php' );
        kdquiz_safe_include( __DIR__ . '/includes/kd-quiz-import.php' );
        kdquiz_safe_include( __DIR__ . '/includes/kd-quiz-editing.php' );   
    }   
});
