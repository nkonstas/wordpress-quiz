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
// Editing
//

//
// Admin menu items
//

add_action('admin_menu', function () {

    add_submenu_page(
        'edit.php?post_type=kd_quiz_question',
        'Quiz Settings',
        'Settings',
        'manage_options',
        'kd-quiz-settings',
        'KDQuiz\kd_quiz_settings_page'
    );

    add_submenu_page(
        'edit.php?post_type=kd_quiz_question', // Parent slug
        'Import Quiz Questions',               // Page title
        'Import Questions',                    // Menu title
        'manage_options',                      // Capability
        'kd_import_quiz_questions',            // Menu slug
        'KDQuiz\kd_import_quiz_questions_page'        // Callback function
    );
});

add_action('init', function () {

    global $kd_quiz_used;
    $kd_quiz_used = false; // Initialize the variable

    $labels = array(
        'name' => 'Quiz Questions',
        'singular_name' => 'Quiz Question',
        'add_new' => 'Add New Question',
        'add_new_item' => 'Add New Question',
        'edit_item' => 'Edit Question',
        'new_item' => 'New Question',
        'all_items' => 'All Questions',
        'view_item' => 'View Question',
        'search_items' => 'Search Questions',
        'not_found' => 'No questions found',
        'not_found_in_trash' => 'No questions found in Trash',
        'menu_name' => 'Quiz Questions'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'label'  => 'Quiz Questions',
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => array('title'), // 'title' is used for the question text
        'publicly_queryable' => false, // Makes CPT not queryable on the front end
        'rewrite' => false, // Disables pretty permalinks
        // other arguments as needed
    );
    register_post_type('kd_quiz_question', $args);
});

add_action('admin_enqueue_scripts', function () {
    // Define the paths to the script and stylesheet relative to the plugin directory
    $script_path = '../assets/kd-admin-quiz.min.js';
    $style_path = '../assets/kd-admin-quiz.min.css';

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
    wp_enqueue_script( 'kd-admin-quiz-script', $script_url, array(), $script_version );
    wp_enqueue_style( 'kd-admin-quiz-style', $style_url, array(), $style_version );
});

add_filter('manage_kd_quiz_question_posts_columns', function ($columns) {
    $columns['kd_correct_answer'] = 'Correct Answer';
    return $columns;
});

add_action('manage_kd_quiz_question_posts_custom_column', function ($column, $post_id) {
    switch ($column) {
        case 'kd_correct_answer':
            $correct_answer_index = get_post_meta($post_id, 'kd_correct_answer', true);
            if (!empty($correct_answer_index)) {
                $correct_answer = get_post_meta($post_id, 'kd_answer_' . $correct_answer_index, true);
                echo esc_html($correct_answer); // Display the correct answer
            } else {
                echo '—'; // Display a placeholder if no answer is set
            }
            break;
    }
}, 10, 2);

add_action('add_meta_boxes', function () {
    add_meta_box(
        'kd_quiz_questions_meta_box',         // Unique ID for the meta box
        'Quiz Question Details',              // Title of the meta box
        'KDQuiz\kd_display_quiz_questions_meta_box', // Callback function to display fields
        'kd_quiz_question'                    // Post type to which to add the box
    );
});

function kd_display_quiz_questions_meta_box($post) {
    wp_nonce_field(plugin_basename(__FILE__), 'kd_quiz_question_nonce');

    // Fields for answers
    $correct_answer = get_post_meta($post->ID, 'kd_correct_answer', true);
    $answers = [];
    for ($i = 0; $i < 4; $i++) {
        $answers[] = get_post_meta($post->ID, 'kd_answer_' . $i, true);
    }

    // HTML for the form
    foreach ($answers as $index => $answer) {
        $answerNumber = $index + 1;
        echo '<label for="kd_answer_' . $answerNumber . '">Answer ' . $answerNumber . ':</label> ';
        echo '<input type="radio" name="kd_correct_answer" value="' . $answerNumber . '" ' . checked($correct_answer, $answerNumber, false) . '> ';
        echo '<input type="text" id="kd_answer_' . $answerNumber . '" name="kd_answer_' . $answerNumber . '" value="' . esc_attr($answer) . '" size="25"><br><br>';
    }

    // Explanation field
    $explanation = get_post_meta($post->ID, 'kd_explanation', true);
    echo '<label for="kd_explanation">Explanation:</label><br>';
    echo '<textarea id="kd_explanation" name="kd_explanation" rows="4" cols="50">' . esc_textarea($explanation) . '</textarea>';
}

add_action('save_post_kd_quiz_question', function ($post_id) {
    // Check if our nonce is set.
    if (!isset($_POST['kd_quiz_question_nonce'])) {
        return;
    }
    // Verify that the nonce is valid.
    if (!wp_verify_nonce($_POST['kd_quiz_question_nonce'], plugin_basename(__FILE__))) {
        return;
    }
    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    // Check the user's permissions.
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save answers
    for ($i = 0; $i < 4; $i++) {
        if (isset($_POST['kd_answer_' . $i])) {
            update_post_meta($post_id, 'kd_answer_' . $i, sanitize_text_field($_POST['kd_answer_' . $i]));
        }
    }

    // Save correct answer
    if (isset($_POST['kd_correct_answer'])) {
        update_post_meta($post_id, 'kd_correct_answer', sanitize_text_field($_POST['kd_correct_answer']));
    }

    // Save explanation
    update_post_meta($post_id, 'kd_explanation', sanitize_textarea_field($_POST['kd_explanation']));
});

add_filter('manage_kd_quiz_question_posts_columns', function ($columns) {
    $columns['kd_stats_engagement'] = 'Engagement';
    $columns['kd_stats_average_score'] = 'Average Score';
    return $columns;
});

add_action('manage_kd_quiz_question_posts_custom_column', function ($column, $question_id) {
    $views = (int) get_post_meta($question_id, 'kd_stats_view_count', true);
    $correct = (int) get_post_meta($question_id, 'kd_stats_correct_count', true);
    $wrong = (int) get_post_meta($question_id, 'kd_stats_wrong_count', true);
    $totalAnswers = $correct + $wrong;

    switch ($column) {
        case 'kd_stats_engagement':
            if ($views === 0) {
                echo '-';
            } else {
                $engagement = ($views > 0) ? $totalAnswers / $views : 0;
                echo number_format($engagement* 100, 1) . '%<br><small>views ' . $views . '</small>'; // Format as percentage
            }
            break;
        case 'kd_stats_average_score':
            if ($correct === 0 && $wrong === 0) {
                echo '-';
            } else {
                $averageScore = ($totalAnswers > 0) ? $correct / $totalAnswers : 0;
                echo number_format($averageScore * 100, 1) . '%<br><small>answers ' . $totalAnswers . '</small>'; // Format as percentage
            }
            break;
    }
}, 10, 2);

add_filter('manage_edit-kd_quiz_question_sortable_columns', function ($columns) {
    $columns['kd_stats_engagement'] = 'kd_stats_engagement';
    $columns['kd_stats_average_score'] = 'kd_stats_average_score';
    return $columns;
});

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');

    switch ($orderby) {
        case 'kd_stats_engagement':
        case 'kd_stats_average_score':
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => $orderby,
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => $orderby,
                    'compare' => 'NOT EXISTS',
                    'value' => '0' // Default value when meta key is missing
                )
            );
            $query->set('meta_query', $meta_query);
            break;
    }
});

add_filter('posts_orderby', function ($orderby_sql, $query) {
    global $wpdb;

    if (!is_admin() || !$query->is_main_query()) {
        return $orderby_sql;
    }

    $orderby = $query->get('orderby');
    $order = strtoupper($query->get('order')) ?: 'DESC'; // Default to 'DESC' if not set

    switch ($orderby) {
        case 'kd_stats_engagement':
        case 'kd_stats_average_score':
            //
            // Stable sort
            //
            // post_metadata wont work, we have to use mt1 since WP defines an mt1 alias. 
            // this may break in the future
            //
            global $wpdb;
            $orderby_sql = "mt1.meta_value+0 $order, {$wpdb->posts}.ID $order";
            break;
    }

    return $orderby_sql;
}, 10, 2);

add_filter('posts_groupby', function($groupby, $query){

    $orderby = $query->get('orderby');
    $order = $query->get('order'); // Get the sort direction ('ASC' or 'DESC')

    switch ($orderby) {
        case 'kd_stats_engagement':
        case 'kd_stats_average_score':
            //
            // Avoid error 1055 in MySql due to the SQL mode ONLY_FULL_GROUP_BY being enabled
            //
            // post_metadata wont work, we have to use mt1 since WP defines an mt1 alias. 
            // this may break in the future
            //
            global $wpdb;
            $groupby .= ', mt1.meta_value';
            break;
    }
   return $groupby;

},10 ,2);
