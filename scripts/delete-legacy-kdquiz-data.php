<?php
/**
 * Usage: wp eval-file scripts/delete-legacy-kdquiz-data.php
 *
 * Utility to copy any kd_quiz_* options/meta into the kdquiz_* namespace and
 * delete the old values before packaging a release.
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "This script must be executed within WordPress.\n");
    exit(1);
}

function kdquiz_cleanup_log($message) {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::log($message);
    } else {
        echo $message . "\n";
    }
}

$option_map = [
    'kd_quiz_number_questions' => 'kdquiz_number_questions',
    'kd_quiz_card_style' => 'kdquiz_card_style',
    'kd_quiz_enable_auto_insert' => 'kdquiz_enable_auto_insert',
    'kd_quiz_heading_selector' => 'kdquiz_heading_selector',
    'kd_quiz_heading_match' => 'kdquiz_heading_match',
    'kd_quiz_min_distance' => 'kdquiz_min_distance',
    'kd_quiz_text_wrong_answer' => 'kdquiz_text_wrong_answer',
    'kd_quiz_text_correct_answer' => 'kdquiz_text_correct_answer',
    'kd_quiz_text_next_question' => 'kdquiz_text_next_question',
    'kd_quiz_text_next_view_score' => 'kdquiz_text_next_view_score',
    'kd_quiz_text_score_grade' => 'kdquiz_text_score_grade',
    'kd_quiz_text_score_grade_a' => 'kdquiz_text_score_grade_a',
    'kd_quiz_text_score_grade_b' => 'kdquiz_text_score_grade_b',
    'kd_quiz_text_score_grade_c' => 'kdquiz_text_score_grade_c',
    'kd_quiz_text_score_grade_f' => 'kdquiz_text_score_grade_f',
    'kd_quiz_text_score_grade_a_message' => 'kdquiz_text_score_grade_a_message',
    'kd_quiz_text_score_grade_b_message' => 'kdquiz_text_score_grade_b_message',
    'kd_quiz_text_score_grade_c_message' => 'kdquiz_text_score_grade_c_message',
    'kd_quiz_text_score_grade_f_message' => 'kdquiz_text_score_grade_f_message',
    'kd_quiz_text_score_percentage' => 'kdquiz_text_score_percentage',
];

foreach ($option_map as $legacy => $target) {
    $legacy_value = get_option($legacy, null);
    if (null === $legacy_value) {
        continue;
    }

    // Only overwrite the target when it hasn't been saved yet.
    $current = get_option($target, null);
    if (null === $current || '' === $current) {
        update_option($target, $legacy_value);
    }

    delete_option($legacy);
}

$meta_map = [
    'kd_correct_answer' => 'kdquiz_correct_answer',
    'kd_explanation' => 'kdquiz_explanation',
    'kd_stats_view_count' => 'kdquiz_stats_view_count',
    'kd_stats_correct_count' => 'kdquiz_stats_correct_count',
    'kd_stats_wrong_count' => 'kdquiz_stats_wrong_count',
    'kd_stats_engagement' => 'kdquiz_stats_engagement',
    'kd_stats_average_score' => 'kdquiz_stats_average_score',
];

for ($i = 0; $i < 4; $i++) {
    $meta_map['kd_answer_' . $i] = 'kdquiz_answer_' . $i;
}

$questions = get_posts([
    'post_type'      => 'kd_quiz_question',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

$updated = 0;
foreach ($questions as $question_id) {
    foreach ($meta_map as $legacy_key => $target_key) {
        $legacy_value = get_post_meta($question_id, $legacy_key, true);
        if ('' === $legacy_value) {
            continue;
        }

        // Preserve data already saved under the target prefix.
        $current = get_post_meta($question_id, $target_key, true);
        if ('' === $current) {
            update_post_meta($question_id, $target_key, $legacy_value);
        }

        delete_post_meta($question_id, $legacy_key);
        $updated++;
    }
}

kdquiz_cleanup_log(sprintf('Processed %d quiz questions; legacy keys removed.', count($questions)));
kdquiz_cleanup_log(sprintf('Legacy meta fields touched: %d', $updated));
kdquiz_cleanup_log('Legacy kd_quiz_* options have been migrated and deleted.');
