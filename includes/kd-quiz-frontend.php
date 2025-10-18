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

namespace KDQuizPlugin;

if (!defined('ABSPATH')) {
    exit;
}

//
// Frontend bootstrap
//

add_shortcode('kdquiz', function () {
    global $kdquiz_shortcode_used;

    // We only ever want one quiz instance per request.
    if ($kdquiz_shortcode_used) {
        return '';
    }

    $kdquiz_shortcode_used = true;

    return '<div id="kdquiz-container"></div>';
});

add_action('wp_enqueue_scripts', function () {
    // Version assets on mtime so browsers pick up fresh bundles after releases.
    $script_path = '../assets/kd-quiz.min.js';
    $style_path  = '../assets/kd-quiz.min.css';

    $script_full_path = plugin_dir_path(__FILE__) . $script_path;
    $style_full_path  = plugin_dir_path(__FILE__) . $style_path;

    $script_version = file_exists($script_full_path) ? (string) filemtime($script_full_path) : false;
    $style_version  = file_exists($style_full_path) ? (string) filemtime($style_full_path) : false;

    $script_url = plugins_url($script_path, __FILE__);
    $style_url  = plugins_url($style_path, __FILE__);

    wp_enqueue_style('kdquiz-style', $style_url, [], $style_version ?: null);
    wp_enqueue_script('kdquiz-script', $script_url, [], $script_version ?: null, true);

    $questions = absint(get_option('kdquiz_number_questions', 5));
    $min_distance = absint(get_option('kdquiz_min_distance', 0));

    $replacements = [
        'ajax_url'            => admin_url('admin-ajax.php'),
        'nonce'               => wp_create_nonce('kdquiz_ajax_nonce'),
        'element_selector'    => '#kdquiz-container',
        'questions'           => $questions > 0 ? $questions : 5,
        'style'               => sanitize_key(get_option('kdquiz_card_style', 'kdquiz_style_1')),
        'auto_insert_enabled' => (int) get_option('kdquiz_enable_auto_insert', 0),
        'heading_selector'    => sanitize_text_field(get_option('kdquiz_heading_selector', 'h2, h3')),
        'heading_match'       => sanitize_text_field(get_option('kdquiz_heading_match', '')),
        'min_distance'        => $min_distance >= 0 ? $min_distance : 0,
    ];

    // Pass through the strings so the JS bundle can localise UI text without extra requests.
    $strings = Shared::getInstance()->getStrings();
    foreach ($strings as $string) {
        $text = get_option($string['option_name'], $string['default_value']);
        $replacements[$string['key'] . '_raw'] = $text;
        $replacements[$string['key']] = wp_kses_post($text);
    }

    wp_localize_script('kdquiz-script', 'kdQuizAjax', $replacements);
});
