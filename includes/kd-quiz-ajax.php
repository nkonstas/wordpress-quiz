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

if (!defined('ABSPATH')) {
    exit;
}

//
// Ajax APIs
//

function kdquiz_update_question_stats($question_id) {
    // Keep the derived metrics in sync whenever an interaction counter moves.
    $views = (int) get_post_meta($question_id, 'kdquiz_stats_view_count', true);
    $views = max(0, $views);

    $correct = (int) get_post_meta($question_id, 'kdquiz_stats_correct_count', true);
    $correct = max(0, $correct);

    $wrong = (int) get_post_meta($question_id, 'kdquiz_stats_wrong_count', true);
    $wrong = max(0, $wrong);

    $engagement    = ($views > 0) ? ($correct + $wrong) / $views : 0;
    $average_score = ($correct + $wrong > 0) ? $correct / ($correct + $wrong) : 0;

    update_post_meta($question_id, 'kdquiz_stats_engagement', $engagement);
    update_post_meta($question_id, 'kdquiz_stats_average_score', $average_score);
}

add_action('wp_ajax_kd_fetch_random_questions', __NAMESPACE__ . '\\kdquiz_fetch_random_questions');
add_action('wp_ajax_nopriv_kd_fetch_random_questions', __NAMESPACE__ . '\\kdquiz_fetch_random_questions');

function kdquiz_fetch_random_questions() {
    check_ajax_referer('kdquiz_ajax_nonce', 'nonce');

    // Enforce sane bounds before the query fires.
    $number_of_questions = isset($_POST['number']) ? absint(wp_unslash($_POST['number'])) : 5;
    $number_of_questions = $number_of_questions > 0 ? $number_of_questions : 5;

    $viewed_param = isset($_POST['viewed_questions']) ? sanitize_textarea_field(wp_unslash($_POST['viewed_questions'])) : '';
    $viewed_questions = [];
    if (is_string($viewed_param) && '' !== trim($viewed_param)) {
        $decoded = json_decode($viewed_param, true);
        if (is_array($decoded)) {
            $viewed_questions = array_map('absint', array_filter($decoded, 'is_numeric'));
        }
    }

    // Avoid using post__not_in for performance (VIP guideline). Fetch a larger
    // random batch and filter out viewed IDs in PHP.
    $fetch_count = max($number_of_questions + count($viewed_questions), $number_of_questions * 2);
    $fetch_count = min($fetch_count, 50);

    $batch = get_posts([
        'post_type'      => 'kd_quiz_question',
        'posts_per_page' => $fetch_count,
        'orderby'        => 'rand',
    ]);

    $questions = [];
    $seen_ids  = [];
    foreach ($batch as $p) {
        if (in_array($p->ID, $viewed_questions, true)) {
            continue;
        }
        if (isset($seen_ids[$p->ID])) {
            continue;
        }
        $questions[]      = $p;
        $seen_ids[$p->ID] = true;
        if (count($questions) >= $number_of_questions) {
            break;
        }
    }

    // If still short, try a few more random pulls and continue filtering.
    $tries = 0;
    while (count($questions) < $number_of_questions && $tries < 3) {
        $extra = get_posts([
            'post_type'      => 'kd_quiz_question',
            'posts_per_page' => ($number_of_questions - count($questions)) * 2,
            'orderby'        => 'rand',
        ]);
        foreach ($extra as $p) {
            if (in_array($p->ID, $viewed_questions, true)) {
                continue;
            }
            if (isset($seen_ids[$p->ID])) {
                continue;
            }
            $questions[]      = $p;
            $seen_ids[$p->ID] = true;
            if (count($questions) >= $number_of_questions) {
                break 2;
            }
        }
        $tries++;
    }

    $data = array_map(function ($post) {
        // Mirror the shape expected by the front-end app.
        $answers = [];
        for ($i = 0; $i < 4; $i++) {
            $answer_text = get_post_meta($post->ID, 'kdquiz_answer_' . $i, true);
            $answers[] = [
                'optionId'   => 'option_id_' . $i,
                'optionText' => wp_kses_post($answer_text),
            ];
        }

        $correct_answer = get_post_meta($post->ID, 'kdquiz_correct_answer', true);
        $explanation    = get_post_meta($post->ID, 'kdquiz_explanation', true);

        return [
            'questionId'      => (int) $post->ID,
            'questionText'    => sanitize_text_field($post->post_title),
            'options'         => $answers,
            'correctOptionId' => 'option_id_' . absint($correct_answer),
            'explanation'     => wp_kses_post($explanation),
        ];
    }, $questions);

    wp_send_json_success($data);
}

add_action('wp_ajax_kd_increment_view_count', __NAMESPACE__ . '\\kdquiz_increment_view_count');
add_action('wp_ajax_nopriv_kd_increment_view_count', __NAMESPACE__ . '\\kdquiz_increment_view_count');

function kdquiz_increment_view_count() {
    check_ajax_referer('kdquiz_ajax_nonce', 'nonce');

    $question_id = isset($_POST['question_id']) ? absint(wp_unslash($_POST['question_id'])) : 0;
    if ($question_id && get_post_type($question_id) === 'kd_quiz_question') {
        // Views only ever increment; defensively guard against negative values.
        $views = (int) get_post_meta($question_id, 'kdquiz_stats_view_count', true);
        $views = max(0, $views);
        update_post_meta($question_id, 'kdquiz_stats_view_count', $views + 1);
        kdquiz_update_question_stats($question_id);
    }

    wp_send_json_success();
}

add_action('wp_ajax_kd_record_answer', __NAMESPACE__ . '\\kdquiz_record_answer');
add_action('wp_ajax_nopriv_kd_record_answer', __NAMESPACE__ . '\\kdquiz_record_answer');

function kdquiz_record_answer() {
    check_ajax_referer('kdquiz_ajax_nonce', 'nonce');

    $question_id = isset($_POST['question_id']) ? absint(wp_unslash($_POST['question_id'])) : 0;
    $is_correct_raw = isset($_POST['is_correct']) ? sanitize_text_field(wp_unslash($_POST['is_correct'])) : '';
    $is_correct  = function_exists('wp_validate_boolean') ? wp_validate_boolean($is_correct_raw) : rest_sanitize_boolean($is_correct_raw);

    if ($question_id && get_post_type($question_id) === 'kd_quiz_question') {
        if ($is_correct) {
            // Increment the correct tally and warm the derived stats cache.
            $correct = (int) get_post_meta($question_id, 'kdquiz_stats_correct_count', true);
            $correct = max(0, $correct);
            update_post_meta($question_id, 'kdquiz_stats_correct_count', $correct + 1);
        } else {
            $wrong = (int) get_post_meta($question_id, 'kdquiz_stats_wrong_count', true);
            $wrong = max(0, $wrong);
            update_post_meta($question_id, 'kdquiz_stats_wrong_count', $wrong + 1);
        }

        kdquiz_update_question_stats($question_id);
    }

    wp_send_json_success();
}
