<?php
/**
 * 
 * NOTICE: This software is free for both personal and commercial use. However, in accordance
 * with the GPL 3.0 license, any modifications or enhancements made to this software must be
 * shared and submitted to the repository. Please review the LICENSE file for more details.
 * 
 * Plugin URI:        https://github.com/nkonstas/wordpress-quiz
 * Author URI:        https://github.com/nkonstas/wordpress-quiz
 * License URI:       https://github.com/nkonstas/wordpress-quiz/blob/main/LICENSE
 */

namespace KDQuiz;

//
// Frontend
//

add_shortcode('kd-quiz', function () {
    global $kd_quiz_used;

    // Check if the shortcode has already been used on the page
    if ($kd_quiz_used) {
        return ''; // Return empty string if already used
    }

    $kd_quiz_used = true; // Mark as used

    // Your quiz content generation logic goes here
    $quiz_content = '<div id="kd-quiz-container"></div>';

    return $quiz_content;
});

add_action( 'wp_enqueue_scripts', function () {
    // Define the paths to the script and stylesheet relative to the plugin directory
    $script_path = '../assets/kd-quiz.min.js';
    $style_path = '../assets/kd-quiz.min.css';

    // Get the full paths of the script and stylesheet on the server
    $script_full_path = plugin_dir_path( __FILE__ ) . $script_path;
    $style_full_path = plugin_dir_path( __FILE__ ) . $style_path;

    // Use filemtime() to get the last modified time of the files
    $script_version = filemtime( $script_full_path );
    $style_version = filemtime( $style_full_path );

    // Get the correct URLs to the script and stylesheet
    $script_url = plugins_url( $script_path, __FILE__ );
    $style_url = plugins_url( $style_path, __FILE__ );

    // Enqueue the script and stylesheet with their file timestamps as versions
    wp_enqueue_style( 'kd-quiz-style', $style_url, array(), $style_version );
    wp_enqueue_script( 'kd-quiz-script', $script_url, array('jquery'), $script_version );

    $replacements = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('kd_quiz_ajax_nonce'),
        'element_selector' => '#kd-quiz-container',
        'questions' => get_option('kd_quiz_number_questions', '5'),
        'style' => get_option('kd_quiz_card_style', 'kd_quiz_style_1'),
        'auto_insert_enabled' => get_option('kd_quiz_enable_auto_insert'),
        'heading_selector' => get_option('kd_quiz_heading_selector'),
        'heading_match' => get_option('kd_quiz_heading_match'),
        'min_distance' => get_option('kd_quiz_min_distance'),
    );

    $strings = Shared::getInstance()->getStrings();
    foreach ($strings as $string) {
        $text = get_option('kd_quiz_' . $string['id'], $string['default_value']);
        $replacements[$string['id'] . '_raw'] = $text;
        $replacements[$string['id']] = esc_html($text);
    }

    wp_localize_script('kd-quiz-script', 'kdQuizAjax', $replacements);
});