<?php
/**
 * KD Core
 *
 * @package           KDQuiz
 * @wordpress-plugin
 * Plugin Name:       Interactive Quiz
 * Plugin URI:        https://github.com/nkonstas/wordpress-quiz
 * Description:       Allows you to create a simple interactive quiz on any page or post
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Nikos Konstas
 * Author URI:        https://github.com/nkonstas/wordpress-quiz
 * License:           GPL v2 or later
 * License URI:       https://github.com/nkonstas/wordpress-quiz/blob/main/LICENSE
 * Text Domain:       kd-quiz
 */

namespace KDQuiz;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

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
    $script_path = '/assets/kd-quiz.min.js';
    $style_path = '/assets/kd-quiz.min.css';

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

    wp_localize_script('kd-quiz-script', 'kdQuizAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('kd_quiz_ajax_nonce'),
        'element_selector' => '#kd-quiz-container',
        'questions' => get_option('kd_quiz_number_questions', '5'),
        'style' => get_option('kd_quiz_card_style', 'kd_quiz_style_1'),
        'auto_insert_enabled' => get_option('kd_quiz_enable_auto_insert'),
        'heading_selector' => get_option('kd_quiz_heading_selector'),
        'heading_match' => get_option('kd_quiz_heading_match'),
        'min_distance' => get_option('kd_quiz_min_distance'),
    ));
});

add_action('admin_enqueue_scripts', function () {
    // Define the paths to the script and stylesheet relative to the plugin directory
    $script_path = '/assets/kd-admin-quiz.min.js';
    $style_path = '/assets/kd-admin-quiz.min.css';

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

//
// Import
//

function kd_import_quiz_questions_page() {
    // HTML form for pasting JSON
    ?>
    <div class="wrap"><h1>Import Quiz Questions</h1>
    <p>Here, you can easily bulk import multiple quiz questions into your system using a structured JSON format. Please follow the guidelines below to ensure your data is correctly formatted and imported:</p>
    <ul>
        <li><strong>JSON Format:</strong> Your data should be in a JSON array format. Each quiz question is an object within this array.</li>
        <li><strong>Question Text:</strong> Include the question text in the <code>"questionText"</code> field.</li>
        <li><strong>Options:</strong> Provide up to four answer options as an array under the <code>"options"</code> field. Each option should have an <code>"optionId"</code> (like <code>"a1"</code>, <code>"a2"</code>, etc.) and <code>"optionText"</code> (the answer text).</li>
        <li><strong>Correct Option:</strong> Specify the ID of the correct answer in the <code>"correctOptionId"</code> field.</li>
        <li><strong>Explanation:</strong> Always include an explanation for the answer in the <code>"explanation"</code> field. This is useful for providing feedback to quiz takers.</li>
    </ul>

    Here's an example JSON:

    <small><pre>
[
{
    &quot;questionText&quot;: &quot;&lt;some question&gt;?&quot;,
    &quot;options&quot;: [
    { &quot;optionId&quot;: &quot;&lt;option id&gt;&quot;, &quot;optionText&quot;: &quot;&lt;answer 1&gt;&quot; },
    { &quot;optionId&quot;: &quot;&lt;option id&gt;&quot;, &quot;optionText&quot;: &quot;&lt;answer 2&gt;&quot; },
    { &quot;optionId&quot;: &quot;&lt;option id&gt;&quot;, &quot;optionText&quot;: &quot;&lt;answer 3&gt;&quot; },
    { &quot;optionId&quot;: &quot;&lt;option id&gt;&quot;, &quot;optionText&quot;: &quot;&lt;answer 4&gt;&quot; }
    ],
    &quot;correctOptionId&quot;: &quot;&lt;an option id&gt;&quot;,
    &quot;explanation&quot;: &quot;&lt;some explanation&gt;&quot;
},
{
    &quot;questionText&quot;: &quot;&lt;some question&gt;?&quot;,
    &quot;options&quot;: [
    { &quot;optionId&quot;: &quot;&lt;option id&gt;&quot;, &quot;optionText&quot;: &quot;&lt;answer 1&gt;&quot; },
    { &quot;optionId&quot;: &quot;&lt;option id&gt;&quot;, &quot;optionText&quot;: &quot;&lt;answer 2&gt;&quot; },
    { &quot;optionId&quot;: &quot;&lt;option id&gt;&quot;, &quot;optionText&quot;: &quot;&lt;answer 3&gt;&quot; },
    { &quot;optionId&quot;: &quot;&lt;option id&gt;&quot;, &quot;optionText&quot;: &quot;&lt;answer 4&gt;&quot; }
    ],
    &quot;correctOptionId&quot;: &quot;&lt;an option id&gt;&quot;,
    &quot;explanation&quot;: &quot;&lt;some explanation&gt;&quot;
}  
]
    </pre></small>

    <form action="" method="post">
    <textarea name="kd_quiz_questions_json" rows="10" cols="50" class="large-text"></textarea>
    <input type="submit" value="Import Questions" class="button button-primary">
    </form></div>
    <?php
}

add_action('admin_init', function () {
    if (isset($_POST['kd_quiz_questions_json'])) {
        $questions_json = stripslashes($_POST['kd_quiz_questions_json']);
        $questions = json_decode($questions_json, true);

        if ($questions) {

            $count_added = 0;
            $count_ignored = 0;
            foreach ($questions as $question) {
                if (!kd_question_exists($question['questionText'])) {
                    kd_create_quiz_question($question);
                    $count_added++;
                } else {
                    $count_ignored++;
                }
            }
               
            // Redirect to the quiz question list with count
            $redirect_url = add_query_arg(array(
                'post_type' => 'kd_quiz_question', // Your CPT slug
                'imported' => $count_added,
                'duplicates' => $count_ignored
            ), admin_url('edit.php'));

            wp_redirect($redirect_url);
            exit;
        }
    }
});

add_action('admin_notices', function () {
    if (isset($_GET['imported']) && is_numeric($_GET['imported'])) {
        $count = intval($_GET['imported']);
        $duplicates = intval($_GET['duplicates']);
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo sprintf(esc_html__('%s questions have been successfully imported, %s duplicates ignored.', 'text-domain'), $count, $duplicates);
        echo '</p></div>';
    }
});

function kd_question_exists($question_text) {
    global $wpdb;
    $post_title = wp_strip_all_tags($question_text);
    $query = "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'kd_quiz_question' AND post_status = 'publish'";
    return $wpdb->get_var($wpdb->prepare($query, $post_title));
}

function kd_create_quiz_question($question) {
    // Create a new quiz question post
    $post_id = wp_insert_post(array(
        'post_title'   => wp_strip_all_tags($question['questionText']),
        'post_content' => '', // Optional content
        'post_status'  => 'publish',
        'post_type'    => 'kd_quiz_question',
    ));

    if ($post_id && !is_wp_error($post_id)) {
        // Map optionId to a zero-based index
        $option_index_map = array();
        foreach ($question['options'] as $index => $option) {
            $option_index_map[$option['optionId']] = $index;
            add_post_meta($post_id, 'kd_answer_' . $index, $option['optionText']);
        }

        // Add correct answer index (zero-based)
        $correct_answer_index = isset($option_index_map[$question['correctOptionId']]) ? $option_index_map[$question['correctOptionId']] : null;
        if ($correct_answer_index !== null) {
            add_post_meta($post_id, 'kd_correct_answer', $correct_answer_index);
        }

        // Add explanation
        add_post_meta($post_id, 'kd_explanation', $question['explanation']);
    }
}

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
            submit_button();
            ?>
        </form>

        <h2>Reset Stats</h2>
        <form method="post">
            <input type="hidden" name="action" value="reset_quiz_stats">
            <input type="submit" class="button button-primary" value="Reset Stats" onclick="return confirm('Are you sure you want to reset all quiz stats? This cannot be undone.');">
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
        'kd_quiz_settings_section', 
        'Quiz Customization', 
        null, 
        'kd-quiz-settings'
    );
    
    add_settings_field(
        'kd_quiz_number_questions', 
        'Number of Questions', 
        'KDQuiz\kd_quiz_number_questions_callback', 
        'kd-quiz-settings', 
        'kd_quiz_settings_section'
    );

    add_settings_field(
        'kd_quiz_card_style', 
        'Card Style', 
        'KDQuiz\kd_quiz_card_style_callback', 
        'kd-quiz-settings', 
        'kd_quiz_settings_section'
    );

    add_settings_field(
        'kd_quiz_enable_auto_insert',
        'Enable Automatic Quiz Insertion',
        'KDQuiz\kd_quiz_enable_auto_insert_callback',
        'kd-quiz-settings', 
        'kd_quiz_settings_section'
    );

    add_settings_field(
        'kd_quiz_heading_selector',
        'Heading Selector',
        'KDQuiz\kd_quiz_heading_selector_callback',
        'kd-quiz-settings', 
        'kd_quiz_settings_section'
    );
    
    add_settings_field(
        'kd_quiz_heading_match',
        'Heading Match Pattern',
        'KDQuiz\kd_quiz_heading_match_callback',
        'kd-quiz-settings', 
        'kd_quiz_settings_section'
    );

    add_settings_field(
        'kd_quiz_min_distance',
        'Minimum Distance from Top (%)',
        'KDQuiz\kd_quiz_min_distance_callback',
        'kd-quiz-settings', 
        'kd_quiz_settings_section'
    );    

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
    $current_value = get_option('kd_quiz_card_style', 'style1'); // Default to 'style1'

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
    $option = get_option('kd_quiz_heading_selector', 'h2');
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

//
// Ajax APIs
//

function kd_update_quiz_question_stats($question_id) {
    $views = (int) get_post_meta($question_id, 'kd_stats_view_count', true);
    $correct = (int) get_post_meta($question_id, 'kd_stats_correct_count', true);
    $wrong = (int) get_post_meta($question_id, 'kd_stats_wrong_count', true);

    $engagement = ($views > 0) ? ($correct + $wrong) / $views : 0;
    $averageScore = ($correct + $wrong > 0) ? $correct / ($correct + $wrong) : 0;

    update_post_meta($question_id, 'kd_stats_engagement', $engagement);
    update_post_meta($question_id, 'kd_stats_average_score', $averageScore);
}

add_action('wp_ajax_kd_fetch_random_questions', 'KDQuiz\kd_fetch_random_questions');
add_action('wp_ajax_nopriv_kd_fetch_random_questions', 'KDQuiz\kd_fetch_random_questions'); // For non-logged-in users

function kd_fetch_random_questions() {
    check_ajax_referer('kd_quiz_ajax_nonce', 'nonce');

    $number_of_questions = isset($_POST['number']) ? intval($_POST['number']) : 5; // Default to 5 questions
    $viewed_questions = isset($_POST['viewed_questions']) ? json_decode(stripslashes($_POST['viewed_questions']), true) : array();

    // Sanitize the viewed_questions array to ensure it contains valid integers
    $viewed_questions = array_filter($viewed_questions, 'is_numeric');
    $viewed_questions = array_map('intval', $viewed_questions);

    $args = array(
        'post_type' => 'kd_quiz_question',
        'post__not_in' => $viewed_questions, // Exclude viewed questions
        'posts_per_page' => $number_of_questions,
        'orderby' => 'rand'
    );

    $questions = get_posts($args);

    // Fallback: If not enough questions, get more without excluding
    if (count($questions) < $number_of_questions) {
        $additional_args = array(
            'post_type' => 'kd_quiz_question',
            'posts_per_page' => $number_of_questions - count($questions),
            'orderby' => 'rand'
        );
        $additional_questions = get_posts($additional_args);
        $questions = array_merge($questions, $additional_questions);
    }

    $data = array_map(function($post) {
        // Format the data as needed

        $answers = [];
        for ($i = 0; $i < 4; $i++) {
            $answers[] = array('optionId' => 'option_id_' . $i, 'optionText' => get_post_meta($post->ID, 'kd_answer_' . $i, true) );
        }

        return array(
            'questionId' => $post->ID,
            'questionText' => $post->post_title,
            'options' => $answers,
            'correctOptionId' => 'option_id_' . get_post_meta($post->ID, 'kd_correct_answer', true),
            'explanation' => get_post_meta($post->ID, 'kd_explanation', true),
        );
    }, $questions);

    wp_send_json_success($data);
}

add_action('wp_ajax_kd_increment_view_count', 'KDQuiz\kd_increment_view_count');
add_action('wp_ajax_nopriv_kd_increment_view_count', 'KDQuiz\kd_increment_view_count'); // For non-logged-in users

function kd_increment_view_count() {
    check_ajax_referer('kd_quiz_ajax_nonce', 'nonce');

    $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;

    if ($question_id && get_post_type($question_id) === 'kd_quiz_question') {
        $views = (int) get_post_meta($question_id, 'kd_stats_view_count', true);
        update_post_meta($question_id, 'kd_stats_view_count', $views + 1);
        kd_update_quiz_question_stats($question_id);
    }

    wp_send_json_success();
}

add_action('wp_ajax_kd_record_answer', 'KDQuiz\kd_record_answer');
add_action('wp_ajax_nopriv_kd_record_answer', 'KDQuiz\kd_record_answer'); // For non-logged-in users

function kd_record_answer() {
    check_ajax_referer('kd_quiz_ajax_nonce', 'nonce');

    $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
    $is_correct = isset($_POST['is_correct']) ? filter_var($_POST['is_correct'], FILTER_VALIDATE_BOOLEAN) : false;

    if ($question_id && get_post_type($question_id) === 'kd_quiz_question') {
        if ($is_correct) {
            $correct = (int) get_post_meta($question_id, 'kd_stats_correct_count', true);
            update_post_meta($question_id, 'kd_stats_correct_count', $correct + 1);
            kd_update_quiz_question_stats($question_id);
        } else {
            $wrong = (int) get_post_meta($question_id, 'kd_stats_wrong_count', true);
            update_post_meta($question_id, 'kd_stats_wrong_count', $wrong + 1);
            kd_update_quiz_question_stats($question_id);
        }
    }

    wp_send_json_success();
}
