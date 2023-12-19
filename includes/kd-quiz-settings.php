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
// Settings
//
function kd_get_quiz_styles() {
    return array(
        'kd_quiz_style_1' => 'Vibrant Look',
        'kd_quiz_style_2' => 'Light Look',
        'kd_quiz_style_3' => 'Dark Look',
        // Add more styles as needed
    );
}

function kd_quiz_settings_page() {
    ?>
    <div class="wrap">
        <h1>Quiz Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('kd_quiz_options_group');
            do_settings_sections('kd-quiz-settings');
            $text_replacements_url = plugin_dir_url(__FILE__) . '../assets/text-replacements.jpg';
            ?>

            <div class="kd-text-replacements">
                <p><strong>Below is an overview on how the text replacements are used in the quiz cards.</strong></p>
                <hr>
                <p><strong>Find out how to customize the style <a target="_blank" href="https://github.com/nkonstas/wordpress-quiz">on the project page</a></strong></p>
                <hr>
                <p><strong>The four grades (A, B, C, F) are defined as:</strong></p>
                <ul>
                    <li>Grade A is a score of at least 90%</li>
                    <li>Grade B is a score of at least 70%</li>
                    <li>Grade C is a score of at least 50%</li>
                    <li>Grade F is anything else (i.e. below 50%)</li>
                </ul>                
                <img src="<?php echo $text_replacements_url;?>">
            </div>
            <?php
            submit_button();
            ?>
        </form>
        <hr style="margin-top: 2rem; margin-bottom: 2rem;">
        <h2>Reset Stats</h2>
        <form method="post">
            <input type="hidden" name="action" value="reset_quiz_stats">
            <input type="submit" class="button button-primary kd-action-destructive" value="Delete All Question Stats" onclick="return confirm('Are you sure you want to reset all quiz stats? This cannot be undone.');">
            <p>This will delete all statistics collected for your Quiz Questions. It will not delete any questions.</p>
        </form>        
    </div>
    <?php
}

add_action('admin_init', function () {
    register_setting('kd_quiz_options_group', 'kd_quiz_number_questions');
    register_setting('kd_quiz_options_group', 'kd_quiz_card_style');
    register_setting('kd_quiz_options_group', 'kd_quiz_enable_auto_insert');
    register_setting('kd_quiz_options_group', 'kd_quiz_heading_selector');
    register_setting('kd_quiz_options_group', 'kd_quiz_heading_match');
    register_setting('kd_quiz_options_group', 'kd_quiz_min_distance');

    add_settings_section(
        'kd_quiz_settings_general_section', 
        'General Settings', 
        null, 
        'kd-quiz-settings'
    );
    
    add_settings_field(
        'kd_quiz_number_questions', 
        'Number of Questions', 
        'KDQuiz\kd_quiz_number_questions_callback', 
        'kd-quiz-settings', 
        'kd_quiz_settings_general_section'
    );

    add_settings_field(
        'kd_quiz_card_style', 
        'Card Style', 
        'KDQuiz\kd_quiz_card_style_callback', 
        'kd-quiz-settings', 
        'kd_quiz_settings_general_section'
    );

    add_settings_section(
        'kd_quiz_settings_text_section', 
        'Text Replacements', 
        null, 
        'kd-quiz-settings'
    );

    add_settings_field(
        'kd_quiz_enable_auto_insert',
        'Enable Automatic Quiz Insertion',
        'KDQuiz\kd_quiz_enable_auto_insert_callback',
        'kd-quiz-settings', 
        'kd_quiz_settings_general_section'
    );

    add_settings_field(
        'kd_quiz_heading_selector',
        'Heading Selector',
        'KDQuiz\kd_quiz_heading_selector_callback',
        'kd-quiz-settings', 
        'kd_quiz_settings_general_section'
    );
    
    add_settings_field(
        'kd_quiz_heading_match',
        'Heading Match Pattern',
        'KDQuiz\kd_quiz_heading_match_callback',
        'kd-quiz-settings', 
        'kd_quiz_settings_general_section'
    );

    add_settings_field(
        'kd_quiz_min_distance',
        'Minimum Distance from Top (%)',
        'KDQuiz\kd_quiz_min_distance_callback',
        'kd-quiz-settings', 
        'kd_quiz_settings_general_section'
    ); 

    $strings = Shared::getInstance()->getStrings();
    foreach ($strings as $string) {

        register_setting('kd_quiz_options_group', 'kd_quiz_' . $string['id']);

        add_settings_field(
            'kd_quiz_' . $string['id'],
            $string['description'],
            function() use ($string) {
                $option = get_option('kd_quiz_' . $string['id'], $string['default_value']);
                echo '<input class="large-text" type="text" name="kd_quiz_' . $string['id'] . '" value="' . esc_attr($option) . '"/>';
            },
            'kd-quiz-settings',
            'kd_quiz_settings_text_section'
        );
    
        // For wp_localize_script
        $script_replacements['text_' . $setting['id']] = get_option('kd_quiz_' . $setting['id']);
    }  

    if (isset($_POST['action']) && $_POST['action'] === 'reset_quiz_stats') {
        // Security check
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        // Reset stats logic
        kd_reset_all_quiz_stats();

        // Optionally add an admin notice to confirm the reset
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Quiz stats have been reset.</p></div>';
        });
    }

});

function kd_reset_all_quiz_stats() {
    // Get all posts of the CPT 'kd_quiz_question'
    $args = array(
        'post_type' => 'kd_quiz_question',
        'posts_per_page' => -1, // Retrieve all posts
        'fields' => 'ids', // Retrieve only the IDs for performance
    );

    $quiz_questions = get_posts($args);

    // Iterate through each post and delete the meta fields
    foreach ($quiz_questions as $question_id) {
        delete_post_meta($question_id, 'kd_stats_view_count');
        delete_post_meta($question_id, 'kd_stats_correct_count');
        delete_post_meta($question_id, 'kd_stats_wrong_count');
        delete_post_meta($question_id, 'kd_stats_engagement');
        delete_post_meta($question_id, 'kd_stats_average_score');
    }
}

function kd_quiz_number_questions_callback() {
    $value = get_option('kd_quiz_number_questions', 5);
    echo '<input type="number" name="kd_quiz_number_questions" value="' . esc_attr($value) . '" />';
}

function kd_quiz_card_style_callback() {
    $styles = kd_get_quiz_styles();
    $current_value = get_option('kd_quiz_card_style', 'kd_quiz_style_1'); // Default to 'kd_quiz_style_1'

    echo '<select name="kd_quiz_card_style">';
    foreach ($styles as $id => $name) {
        $selected = ($current_value == $id) ? 'selected' : '';
        echo '<option value="' . esc_attr($id) . '" ' . $selected . '>' . esc_html($name) . '</option>';
    }
    echo '</select>';
}

function kd_quiz_enable_auto_insert_callback() {
    $option = get_option('kd_quiz_enable_auto_insert', 0);
    echo '<input type="checkbox" name="kd_quiz_enable_auto_insert" value="1" ' . checked(1, $option, false) . '/>';
}

function kd_quiz_heading_selector_callback() {
    $option = get_option('kd_quiz_heading_selector', 'h2, h3');
    echo '<input type="text" name="kd_quiz_heading_selector" value="' . esc_attr($option) . '"/>';
}

function kd_quiz_heading_match_callback() {
    $option = get_option('kd_quiz_heading_match');
    echo '<input type="text" name="kd_quiz_heading_match" value="' . esc_attr($option) . '"/>';
}

function kd_quiz_min_distance_callback() {
    $option = get_option('kd_quiz_min_distance', "0");
    echo '<input type="number" name="kd_quiz_min_distance" value="' . esc_attr($option) . '" min="0" max="100" step="1"/> %';
}
