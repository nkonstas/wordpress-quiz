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
