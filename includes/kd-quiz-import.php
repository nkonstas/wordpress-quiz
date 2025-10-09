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
// Import
//

function kdquiz_import_questions_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Import Quiz Questions', 'kd-quiz'); ?></h1>
        <p><?php esc_html_e('Bulk import multiple quiz questions using a structured JSON array. Each entry must include the question text, up to four answer options, the correct option id, and an explanation.', 'kd-quiz'); ?></p>
        <form action="" method="post">
            <?php wp_nonce_field('kdquiz_import_questions_action', 'kdquiz_import_questions_nonce'); ?>
            <textarea name="kdquiz_questions_json" rows="10" cols="50" class="large-text" placeholder='[ {"questionText":"..."} ]'></textarea>
            <p>
                <input type="submit" value="<?php esc_attr_e('Import Questions', 'kd-quiz'); ?>" class="button button-primary">
            </p>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {
    if (!isset($_POST['kdquiz_questions_json'])) {
        return;
    }

    // Treat the import endpoint like a mini API – nonce + capability required.
    if (!isset($_POST['kdquiz_import_questions_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kdquiz_import_questions_nonce'])), 'kdquiz_import_questions_action')) {
        wp_die(esc_html__('Security check failed. Please try again.', 'kd-quiz'));
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to import questions.', 'kd-quiz'));
    }

    $questions_json = wp_unslash($_POST['kdquiz_questions_json']);
    $questions      = json_decode($questions_json, true);

    if (!is_array($questions)) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Invalid JSON payload. Please review your input.', 'kd-quiz') . '</p></div>';
        });
        return;
    }

    $count_added   = 0;
    $count_ignored = 0;

    foreach ($questions as $question) {
        if (!kdquiz_is_valid_question_payload($question)) {
            $count_ignored++;
            continue;
        }

        $question_text = sanitize_text_field($question['questionText']);
        if (!kdquiz_question_exists($question_text)) {
            // First time we see this question, store it.
            kdquiz_create_question($question);
            $count_added++;
        } else {
            $count_ignored++;
        }
    }

    $redirect_url = add_query_arg(
        [
            'post_type'  => 'kd_quiz_question',
            'imported'   => $count_added,
            'duplicates' => $count_ignored,
        ],
        admin_url('edit.php')
    );

    wp_redirect($redirect_url);
    exit;
});

add_action('admin_notices', function () {
    if (!isset($_GET['imported'], $_GET['duplicates'])) {
        return;
    }

    $count      = absint($_GET['imported']);
    $duplicates = absint($_GET['duplicates']);

    if (0 === $count && 0 === $duplicates) {
        return;
    }

    printf(
        '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
        sprintf(
            esc_html__('%1$s questions imported, %2$s duplicates ignored.', 'kd-quiz'),
            esc_html(number_format_i18n($count)),
            esc_html(number_format_i18n($duplicates))
        )
    );
});

function kdquiz_is_valid_question_payload($question) {
    if (!is_array($question)) {
        return false;
    }

    $required_keys = ['questionText', 'options', 'correctOptionId', 'explanation'];
    foreach ($required_keys as $key) {
        if (!array_key_exists($key, $question)) {
            return false;
        }
    }

    if (!is_array($question['options']) || empty($question['options'])) {
        return false;
    }

    return true;
}

function kdquiz_question_exists($question_text) {
    global $wpdb;

    $post_title = wp_strip_all_tags($question_text);
    // Using a raw query keeps things fast when people import hundreds of questions.
    $query      = "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'kd_quiz_question' AND post_status = 'publish'";

    return (bool) $wpdb->get_var($wpdb->prepare($query, $post_title));
}

function kdquiz_create_question($question) {
    $question_text = sanitize_text_field($question['questionText']);

    $post_id = wp_insert_post(
        [
            'post_title'  => $question_text,
            'post_status' => 'publish',
            'post_type'   => 'kd_quiz_question',
        ],
        true
    );

    if (is_wp_error($post_id) || !$post_id) {
        return;
    }

    $option_index_map = [];
    foreach ($question['options'] as $index => $option) {
        if (!isset($option['optionId'], $option['optionText'])) {
            continue;
        }

        $option_index_map[$option['optionId']] = $index;
        update_post_meta($post_id, 'kdquiz_answer_' . $index, sanitize_text_field($option['optionText']));
    }

    if (isset($option_index_map[$question['correctOptionId']])) {
        update_post_meta($post_id, 'kdquiz_correct_answer', absint($option_index_map[$question['correctOptionId']]));
    }

    if (isset($question['explanation'])) {
        update_post_meta($post_id, 'kdquiz_explanation', wp_kses_post($question['explanation']));
    }
}
