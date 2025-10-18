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
// Import
//

function kdquiz_import_questions_page() {
    $example_payload = wp_json_encode(
        [
            [
                'questionText'    => 'What is the capital of France?',
                'options'         => [
                    ['optionId' => 'option_id_0', 'optionText' => 'Paris'],
                    ['optionId' => 'option_id_1', 'optionText' => 'Berlin'],
                    ['optionId' => 'option_id_2', 'optionText' => 'Madrid'],
                    ['optionId' => 'option_id_3', 'optionText' => 'Rome'],
                ],
                'correctOptionId' => 'option_id_0',
                'explanation'     => 'Paris has been the capital of France since 508 A.D.',
            ],
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Import Quiz Questions', 'kd-quiz'); ?></h1>
        <p><?php esc_html_e('Bulk import multiple quiz questions using a structured JSON array. Each entry must include the question text, up to four answer options, the correct option id, and an explanation.', 'kd-quiz'); ?></p>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <?php wp_nonce_field('kdquiz_import_questions_action', 'kdquiz_import_questions_nonce'); ?>
            <input type="hidden" name="action" value="kdquiz_import_questions">
            <textarea name="kdquiz_questions_json" rows="10" cols="50" class="large-text" placeholder='[ {"questionText":"..."} ]'></textarea>
            <p><strong><?php esc_html_e('Example JSON structure', 'kd-quiz'); ?></strong></p>
            <pre class="kdquiz-import-example"><code><?php echo esc_html($example_payload); ?></code></pre>
            <p>
                <input type="submit" value="<?php esc_attr_e('Import Questions', 'kd-quiz'); ?>" class="button button-primary">
            </p>
        </form>
    </div>
    <?php
}

function kdquiz_import_questions_handler() {
    if (!isset($_POST['kdquiz_questions_json'])) {
        $redirect_url = add_query_arg(
            [
                'page'                 => 'kdquiz-import-questions',
                'status'               => 'missing',
                'kdquiz_import_notice' => wp_create_nonce('kdquiz_import_notice'),
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    // Treat the import endpoint like a mini API – nonce + capability required.
    if (!isset($_POST['kdquiz_import_questions_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kdquiz_import_questions_nonce'])), 'kdquiz_import_questions_action')) {
        wp_die(esc_html__('Security check failed. Please try again.', 'kd-quiz'));
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to import questions.', 'kd-quiz'));
    }

    $questions_json = sanitize_textarea_field(wp_unslash($_POST['kdquiz_questions_json']));
    $questions      = json_decode($questions_json, true);

    if (!is_array($questions)) {
        $redirect_url = add_query_arg(
            [
                'page'                 => 'kdquiz-import-questions',
                'status'               => 'invalid_json',
                'kdquiz_import_notice' => wp_create_nonce('kdquiz_import_notice'),
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
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
            'post_type'  => 'kdquiz_question',
            'imported'   => $count_added,
            'duplicates' => $count_ignored,
            'kdquiz_import_notice' => wp_create_nonce('kdquiz_import_notice'),
        ],
        admin_url('edit.php')
    );

    wp_safe_redirect($redirect_url);
    exit;
}

add_action('admin_post_kdquiz_import_questions', __NAMESPACE__ . '\\kdquiz_import_questions_handler');

add_action('admin_notices', function () {
    if (!isset($_GET['kdquiz_import_notice'])) {
        return;
    }

    $nonce = sanitize_text_field(wp_unslash($_GET['kdquiz_import_notice']));
    if (!wp_verify_nonce($nonce, 'kdquiz_import_notice')) {
        return;
    }

    $page      = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';

    if ('kdquiz-import-questions' !== $page && 'kdquiz_question' !== $post_type) {
        return;
    }

    if (isset($_GET['imported'], $_GET['duplicates'])) {
        $count      = absint($_GET['imported']);
        $duplicates = absint($_GET['duplicates']);

        if (0 === $count && 0 === $duplicates) {
            return;
        }

        /* translators: 1: number of imported questions, 2: number of duplicates ignored. */
        $kdquiz_import_tpl = __('%1$s questions imported, %2$s duplicates ignored.', 'kd-quiz');
        $kdquiz_import_msg = sprintf(
            $kdquiz_import_tpl,
            number_format_i18n($count),
            number_format_i18n($duplicates)
        );
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html($kdquiz_import_msg)
        );
        return;
    }

    if (!isset($_GET['status'])) {
        return;
    }

    $status = sanitize_key(wp_unslash($_GET['status']));
    $messages = [
        'missing'      => __('Please provide quiz data before attempting the import.', 'kd-quiz'),
        'invalid_json' => __('Invalid JSON payload. Please review your input.', 'kd-quiz'),
    ];

    if (!isset($messages[$status])) {
        return;
    }

    printf(
        '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
        esc_html($messages[$status])
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
    $post_title = wp_strip_all_tags($question_text);

    $query = new \WP_Query([
        'post_type'      => ['kdquiz_question', 'kd_quiz_question'],
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'fields'         => 'ids',
        's'              => $post_title,
    ]);

    if (empty($query->posts)) {
        return false;
    }

    foreach ($query->posts as $post_id) {
        if (get_the_title($post_id) === $post_title) {
            return true;
        }
    }

    return false;
}

function kdquiz_create_question($question) {
    $question_text = sanitize_text_field($question['questionText']);

    $post_id = wp_insert_post(
        [
            'post_title'  => $question_text,
            'post_status' => 'publish',
            'post_type'   => 'kdquiz_question',
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
