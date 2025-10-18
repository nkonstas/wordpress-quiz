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
// Editing
//

add_action('admin_menu', function () {
    // Slot our settings/import pages right under the CPT menu.
    add_submenu_page(
        'edit.php?post_type=kdquiz_question',
        __('Quiz Settings', 'kd-quiz'),
        __('Settings', 'kd-quiz'),
        'manage_options',
        'kdquiz-settings',
        __NAMESPACE__ . '\\kdquiz_settings_page'
    );

    add_submenu_page(
        'edit.php?post_type=kdquiz_question',
        __('Import Quiz Questions', 'kd-quiz'),
        __('Import Questions', 'kd-quiz'),
        'manage_options',
        'kdquiz-import-questions',
        __NAMESPACE__ . '\\kdquiz_import_questions_page'
    );
});

function kdquiz_register_question_post_type() {
    global $kdquiz_shortcode_used;
    $kdquiz_shortcode_used = false;

    // Custom post type registration happens early so the admin menus look right.
    $labels = [
        'name'               => __('Quiz Questions', 'kd-quiz'),
        'singular_name'      => __('Quiz Question', 'kd-quiz'),
        'add_new'            => __('Add New Question', 'kd-quiz'),
        'add_new_item'       => __('Add New Question', 'kd-quiz'),
        'edit_item'          => __('Edit Question', 'kd-quiz'),
        'new_item'           => __('New Question', 'kd-quiz'),
        'all_items'          => __('All Questions', 'kd-quiz'),
        'view_item'          => __('View Question', 'kd-quiz'),
        'search_items'       => __('Search Questions', 'kd-quiz'),
        'not_found'          => __('No questions found', 'kd-quiz'),
        'not_found_in_trash' => __('No questions found in Trash', 'kd-quiz'),
        'menu_name'          => __('Quiz Questions', 'kd-quiz'),
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'label'              => __('Quiz Questions', 'kd-quiz'),
        'menu_icon'          => 'dashicons-welcome-learn-more',
        'supports'           => ['title'],
        'publicly_queryable' => false,
        'rewrite'            => false,
    ];

    register_post_type('kdquiz_question', $args);
}

add_action('init', __NAMESPACE__ . '\\kdquiz_register_question_post_type');

function kdquiz_migrate_legacy_post_type() {
    if (get_option('kdquiz_post_type_migrated')) {
        return;
    }

    global $wpdb;
    $updated = $wpdb->update(
        $wpdb->posts,
        ['post_type' => 'kdquiz_question'],
        ['post_type' => 'kd_quiz_question']
    );

    if (false === $updated) {
        return;
    }

    update_option('kdquiz_post_type_migrated', 1);
}

add_action('init', __NAMESPACE__ . '\\kdquiz_migrate_legacy_post_type', 11);

add_action('admin_enqueue_scripts', function () {
    // Enqueue only the admin CSS; there is no admin JS at present.
    $style_path = '../assets/kd-admin-quiz.min.css';

    $style_full_path = plugin_dir_path(__FILE__) . $style_path;
    $style_version   = file_exists($style_full_path) ? (string) filemtime($style_full_path) : false;
    $style_url       = plugins_url($style_path, __FILE__);

    wp_enqueue_style('kdquiz-admin-quiz-style', $style_url, [], $style_version ?: null);
});

add_filter('manage_kdquiz_question_posts_columns', function ($columns) {
    $columns['kdquiz_correct_answer'] = __('Correct Answer', 'kd-quiz');
    return $columns;
});

add_action('manage_kdquiz_question_posts_custom_column', function ($column, $post_id) {
    if ('kdquiz_correct_answer' !== $column) {
        return;
    }

    $correct_answer_index = get_post_meta($post_id, 'kdquiz_correct_answer', true);
    if ('' === $correct_answer_index && '0' !== $correct_answer_index) {
        echo '&#8212;';
        return;
    }

    $answer_text = get_post_meta($post_id, 'kdquiz_answer_' . (int) $correct_answer_index, true);
    echo esc_html($answer_text);
}, 10, 2);

add_action('add_meta_boxes', function () {
    // The questions CPT stores all quiz configuration in meta.
    add_meta_box(
        'kdquiz_questions_meta_box',
        __('Quiz Question Details', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_display_meta_box',
        'kdquiz_question'
    );
});

function kdquiz_display_meta_box($post) {
    wp_nonce_field('kdquiz_question_action', 'kdquiz_question_nonce');

    $correct_answer = get_post_meta($post->ID, 'kdquiz_correct_answer', true);

    echo '<fieldset class="kdquiz-answer-group">';
    echo '<legend>' . esc_html__('Answers', 'kd-quiz') . '</legend>';
    echo '<p class="description kdquiz-answer-helper">' . esc_html__('Select one option below as the correct answer, then enter the answer text.', 'kd-quiz') . '</p>';

    $active_status_text = esc_html__('Correct choice', 'kd-quiz');

    for ($i = 0; $i < 4; $i++) {
        $answer_text = get_post_meta($post->ID, 'kdquiz_answer_' . $i, true);
        // translators: %d is the answer number shown to editors (1–4).
        $label       = sprintf(__('Answer %d:', 'kd-quiz'), $i + 1);

        $answer_id   = 'kdquiz_answer_' . $i;
        $radio_id    = 'kdquiz_correct_answer_' . $i;
        $row_classes = 'kdquiz-answer-row';

        if ((int) $correct_answer === $i) {
            $row_classes .= ' is-selected';
        }

        printf('<div class="%s">', esc_attr($row_classes));

        echo '<div class="kdquiz-answer-select">';
        printf(
            '<input type="radio" class="kdquiz-answer-radio" id="%1$s" name="%2$s" value="%3$d" %4$s />',
            esc_attr($radio_id),
            esc_attr('kdquiz_correct_answer'),
            esc_attr($i),
            checked((int) $correct_answer, $i, false)
        );
        printf(
            '<label for="%1$s" class="kdquiz-answer-toggle-label">%2$s</label>',
            esc_attr($radio_id),
            esc_html__('Mark as correct', 'kd-quiz')
        );

        $status_attributes = [
            'class="kdquiz-answer-status"',
            'data-active-text="' . esc_attr($active_status_text) . '"',
        ];

        if ((int) $correct_answer === $i) {
            $status_attributes[] = 'role="status"';
            printf('<span %1$s>%2$s</span>', implode(' ', $status_attributes), $active_status_text);
        } else {
            $status_attributes[] = 'aria-hidden="true"';
            printf('<span %s></span>', implode(' ', $status_attributes));
        }
        echo '</div>';

        echo '<div class="kdquiz-answer-fields">';
        printf(
            '<label class="kdquiz-answer-input-label" for="%1$s">%2$s</label>',
            esc_attr($answer_id),
            esc_html($label)
        );
        printf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" size="25" />',
            esc_attr($answer_id),
            esc_attr($answer_text)
        );
        echo '</div>';

        echo '</div>';
    }

    echo '</fieldset>';

    $explanation = get_post_meta($post->ID, 'kdquiz_explanation', true);

    printf('<label for="%1$s">%2$s</label><br>', esc_attr('kdquiz_explanation'), esc_html__('Explanation:', 'kd-quiz'));
    printf(
        '<textarea id="%1$s" name="%1$s" rows="4" cols="50">%2$s</textarea>',
        esc_attr('kdquiz_explanation'),
        esc_textarea($explanation)
    );
}

add_action('save_post_kdquiz_question', function ($post_id) {
    if (!isset($_POST['kdquiz_question_nonce'])) {
        return;
    }

    // Don't mix kses with unsanitised data – bail on nonce failure.
    $nonce = sanitize_text_field(wp_unslash($_POST['kdquiz_question_nonce']));
    if (!wp_verify_nonce($nonce, 'kdquiz_question_action')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    for ($i = 0; $i < 4; $i++) {
        $field_name = 'kdquiz_answer_' . $i;
        if (isset($_POST[$field_name])) {
            $value = sanitize_text_field(wp_unslash($_POST[$field_name]));
            update_post_meta($post_id, $field_name, $value);
        } else {
            delete_post_meta($post_id, $field_name);
        }
    }

    if (isset($_POST['kdquiz_correct_answer'])) {
        $correct_answer = absint(wp_unslash($_POST['kdquiz_correct_answer']));
        update_post_meta($post_id, 'kdquiz_correct_answer', min(3, $correct_answer));
    }

    if (isset($_POST['kdquiz_explanation'])) {
        $explanation = wp_kses_post(wp_unslash($_POST['kdquiz_explanation']));
        update_post_meta($post_id, 'kdquiz_explanation', $explanation);
    }
});

add_action('admin_print_footer_scripts-post.php', __NAMESPACE__ . '\\kdquiz_render_admin_answer_script');
add_action('admin_print_footer_scripts-post-new.php', __NAMESPACE__ . '\\kdquiz_render_admin_answer_script');

/**
 * Injects a lightweight script that keeps the answer rows in sync with the selected radio button.
 */
function kdquiz_render_admin_answer_script() {
    global $post_type;

    if ('kdquiz_question' !== $post_type) {
        return;
    }
    ?>
    <script>
    (function () {
        function initKdQuizAdminAnswers() {
        var answerRows = Array.prototype.slice.call(document.querySelectorAll('.kdquiz-answer-row'));
        if (!answerRows.length) {
            return;
        }

        function syncAnswerState() {
            var selected = document.querySelector('.kdquiz-answer-radio:checked');
            answerRows.forEach(function (row) {
                var radio = row.querySelector('.kdquiz-answer-radio');
                var status = row.querySelector('.kdquiz-answer-status');
                var isSelected = radio === selected;

                row.classList.toggle('is-selected', Boolean(isSelected));

                if (!status) {
                    return;
                }

                var activeText = status.getAttribute('data-active-text') || '';
                if (isSelected) {
                    status.textContent = activeText;
                    status.removeAttribute('aria-hidden');
                    status.setAttribute('role', 'status');
                } else {
                    status.textContent = '';
                    status.setAttribute('aria-hidden', 'true');
                    status.removeAttribute('role');
                }
            });
        }

        var radios = Array.prototype.slice.call(document.querySelectorAll('.kdquiz-answer-radio'));
        radios.forEach(function (radio) {
            radio.addEventListener('change', syncAnswerState);
        });

        syncAnswerState();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initKdQuizAdminAnswers);
        } else {
            initKdQuizAdminAnswers();
        }
    })();
    </script>
    <?php
}

add_filter('manage_kdquiz_question_posts_columns', function ($columns) {
    $columns['kdquiz_stats_engagement']    = __('Engagement', 'kd-quiz');
    $columns['kdquiz_stats_average_score'] = __('Average Score', 'kd-quiz');
    return $columns;
});

add_action('manage_kdquiz_question_posts_custom_column', function ($column, $question_id) {
    if ('kdquiz_stats_engagement' === $column) {
        $views        = (int) get_post_meta($question_id, 'kdquiz_stats_view_count', true);
        $views        = max(0, $views);
        $correct      = (int) get_post_meta($question_id, 'kdquiz_stats_correct_count', true);
        $correct      = max(0, $correct);
        $wrong        = (int) get_post_meta($question_id, 'kdquiz_stats_wrong_count', true);
        $wrong        = max(0, $wrong);
        $totalAnswers = $correct + $wrong;

        if (0 === $views) {
            echo '&#8212;';
            return;
        }

        $engagement = $totalAnswers > 0 ? ($totalAnswers / $views) * 100 : 0;
        printf(
            '%1$s%%<br><small>%2$s %3$s</small>',
            esc_html(number_format_i18n($engagement, 1)),
            esc_html__('views', 'kd-quiz'),
            esc_html(number_format_i18n($views))
        );
        return;
    }

    if ('kdquiz_stats_average_score' === $column) {
        $correct = (int) get_post_meta($question_id, 'kdquiz_stats_correct_count', true);
        $wrong   = (int) get_post_meta($question_id, 'kdquiz_stats_wrong_count', true);
        $correct = max(0, $correct);
        $wrong   = max(0, $wrong);
        $total   = $correct + $wrong;

        if (0 === $total) {
            echo '&#8212;';
            return;
        }

        // Show a quick-glance win rate to help editors tune question difficulty.
        $average = ($correct / $total) * 100;
        printf(
            '%1$s%%<br><small>%2$s %3$s</small>',
            esc_html(number_format_i18n($average, 1)),
            esc_html__('answers', 'kd-quiz'),
            esc_html(number_format_i18n($total))
        );
    }
}, 10, 2);

add_filter('manage_edit-kdquiz_question_sortable_columns', function ($columns) {
    $columns['kdquiz_stats_engagement']    = 'kdquiz_stats_engagement';
    $columns['kdquiz_stats_average_score'] = 'kdquiz_stats_average_score';
    return $columns;
});
