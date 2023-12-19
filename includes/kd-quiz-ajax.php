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
