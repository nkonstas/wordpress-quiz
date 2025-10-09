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
// Settings
//

function kdquiz_get_quiz_styles() {
    // Options exposed to editors; keep ids in sync with the CSS bundle.
    return [
        'kd_quiz_style_1'    => 'Vibrant Look',
        'kd_quiz_style_2'    => 'Light Look',
        'kd_quiz_style_3'    => 'Dark Look',
        'kd_quiz_style_custom' => 'Custom',
    ];
}

function kdquiz_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Quiz Settings', 'kd-quiz'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('kdquiz_options_group');
            do_settings_sections('kd-quiz-settings');
            $text_replacements_url = plugin_dir_url(__FILE__) . '../assets/text-replacements.jpg';
            ?>

            <div class="kd-text-replacements">
                <!-- Quick primer for anyone customising quiz copy without reading the docs. -->
                <p><strong><?php esc_html_e('Below is an overview on how the text replacements are used in the quiz cards.', 'kd-quiz'); ?></strong></p>
                <hr>
                <p><strong>
                    <?php esc_html_e('Find out how to customize the style', 'kd-quiz'); ?>
                    <a target="_blank" rel="noopener noreferrer" href="https://github.com/nkonstas/wordpress-quiz">
                        <?php esc_html_e('on the project page', 'kd-quiz'); ?>
                    </a>
                </strong></p>
                <hr>
                <p><strong><?php esc_html_e('The four grades (A, B, C, F) are defined as:', 'kd-quiz'); ?></strong></p>
                <ul>
                    <li><?php esc_html_e('Grade A is a score of at least 90%', 'kd-quiz'); ?></li>
                    <li><?php esc_html_e('Grade B is a score of at least 70%', 'kd-quiz'); ?></li>
                    <li><?php esc_html_e('Grade C is a score of at least 50%', 'kd-quiz'); ?></li>
                    <li><?php esc_html_e('Grade F is anything else (i.e. below 50%)', 'kd-quiz'); ?></li>
                </ul>
                <img src="<?php echo esc_url($text_replacements_url); ?>" alt="<?php esc_attr_e('Quiz card annotations', 'kd-quiz'); ?>">
            </div>
            <?php submit_button(); ?>
        </form>
        <hr style="margin-top: 2rem; margin-bottom: 2rem;">
        <h2><?php esc_html_e('Reset Stats', 'kd-quiz'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('kdquiz_reset_stats_action', 'kdquiz_reset_stats_nonce'); ?>
            <input type="hidden" name="kdquiz_action" value="reset_quiz_stats">
            <input type="submit" class="button button-primary kd-action-destructive" value="<?php esc_attr_e('Delete All Question Stats', 'kd-quiz'); ?>" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to reset all quiz stats? This cannot be undone.', 'kd-quiz')); ?>');">
            <p><?php esc_html_e('This will delete all statistics collected for your Quiz Questions. It will not delete any questions.', 'kd-quiz'); ?></p>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {
    // Register every surface we expose through the settings UI up front.
    register_setting('kdquiz_options_group', 'kdquiz_number_questions', ['sanitize_callback' => 'absint']);
    register_setting('kdquiz_options_group', 'kdquiz_card_style', [
        'sanitize_callback' => function ($value) {
            $styles = kdquiz_get_quiz_styles();
            return array_key_exists($value, $styles) ? $value : 'kd_quiz_style_1';
        },
    ]);
    register_setting('kdquiz_options_group', 'kdquiz_enable_auto_insert', ['sanitize_callback' => function ($value) {
        return (int) (bool) $value;
    }]);
    register_setting('kdquiz_options_group', 'kdquiz_heading_selector', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('kdquiz_options_group', 'kdquiz_heading_match', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('kdquiz_options_group', 'kdquiz_min_distance', ['sanitize_callback' => function ($value) {
        $value = absint($value);
        return ($value > 100) ? 100 : $value;
    }]);

    add_settings_section(
        'kdquiz_settings_general_section',
        __('General Settings', 'kd-quiz'),
        null,
        'kd-quiz-settings'
    );

    add_settings_field(
        'kdquiz_number_questions',
        __('Number of Questions', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_number_questions_field',
        'kd-quiz-settings',
        'kdquiz_settings_general_section'
    );

    add_settings_field(
        'kdquiz_card_style',
        __('Card Style', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_card_style_field',
        'kd-quiz-settings',
        'kdquiz_settings_general_section'
    );

    add_settings_field(
        'kdquiz_enable_auto_insert',
        __('Enable Automatic Quiz Insertion', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_enable_auto_insert_field',
        'kd-quiz-settings',
        'kdquiz_settings_general_section'
    );

    add_settings_field(
        'kdquiz_heading_selector',
        __('Heading Selector', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_heading_selector_field',
        'kd-quiz-settings',
        'kdquiz_settings_general_section'
    );

    add_settings_field(
        'kdquiz_heading_match',
        __('Heading Match Pattern', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_heading_match_field',
        'kd-quiz-settings',
        'kdquiz_settings_general_section'
    );

    add_settings_field(
        'kdquiz_min_distance',
        __('Minimum Distance from Top (%)', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_min_distance_field',
        'kd-quiz-settings',
        'kdquiz_settings_general_section'
    );

    add_settings_section(
        'kdquiz_settings_text_section',
        __('Text Replacements', 'kd-quiz'),
        null,
        'kd-quiz-settings'
    );

    foreach (Shared::getInstance()->getStrings() as $string) {
        register_setting('kdquiz_options_group', $string['option_name'], ['sanitize_callback' => 'wp_kses_post']);

        add_settings_field(
            $string['option_name'],
            esc_html($string['description']),
            function () use ($string) {
                $option = get_option($string['option_name'], $string['default_value']);
                printf(
                    '<input class="large-text" type="text" name="%1$s" value="%2$s" />',
                    esc_attr($string['option_name']),
                    esc_attr($option)
                );
            },
            'kd-quiz-settings',
            'kdquiz_settings_text_section'
        );
    }

    if (isset($_POST['kdquiz_action']) && 'reset_quiz_stats' === sanitize_key(wp_unslash($_POST['kdquiz_action'] ?? ''))) {
        if (!isset($_POST['kdquiz_reset_stats_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kdquiz_reset_stats_nonce'])), 'kdquiz_reset_stats_action')) {
            wp_die(esc_html__('Security check failed. Please try again.', 'kd-quiz'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'kd-quiz'));
        }

        // Nukes the counters so editors can start A/B tests fresh.
        kdquiz_reset_all_stats();

        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Quiz stats have been reset.', 'kd-quiz') . '</p></div>';
        });
    }
});

function kdquiz_reset_all_stats() {
    $questions = get_posts([
        'post_type'      => 'kd_quiz_question',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    foreach ($questions as $question_id) {
        delete_post_meta($question_id, 'kdquiz_stats_view_count');
        delete_post_meta($question_id, 'kdquiz_stats_correct_count');
        delete_post_meta($question_id, 'kdquiz_stats_wrong_count');
        delete_post_meta($question_id, 'kdquiz_stats_engagement');
        delete_post_meta($question_id, 'kdquiz_stats_average_score');
    }
}

function kdquiz_number_questions_field() {
    $value = get_option('kdquiz_number_questions', 5);
    printf(
        '<input type="number" name="%1$s" value="%2$s" min="1" max="20" />',
        esc_attr('kdquiz_number_questions'),
        esc_attr((int) $value)
    );
}

function kdquiz_card_style_field() {
    $styles = kdquiz_get_quiz_styles();
    $current_value = get_option('kdquiz_card_style', 'kd_quiz_style_1');

    echo '<select name="' . esc_attr('kdquiz_card_style') . '">';
    foreach ($styles as $id => $name) {
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr($id),
            selected($current_value, $id, false),
            esc_html($name)
        );
    }
    echo '</select>';
}

function kdquiz_enable_auto_insert_field() {
    $option = (int) get_option('kdquiz_enable_auto_insert', 0);
    printf(
        '<input type="checkbox" name="%1$s" value="1" %2$s />',
        esc_attr('kdquiz_enable_auto_insert'),
        checked(1, $option, false)
    );
}

function kdquiz_heading_selector_field() {
    $option = get_option('kdquiz_heading_selector', 'h2, h3');
    printf(
        '<input type="text" name="%1$s" value="%2$s" />',
        esc_attr('kdquiz_heading_selector'),
        esc_attr($option)
    );
}

function kdquiz_heading_match_field() {
    $option = get_option('kdquiz_heading_match', '');
    printf(
        '<input type="text" name="%1$s" value="%2$s" />',
        esc_attr('kdquiz_heading_match'),
        esc_attr($option)
    );
}

function kdquiz_min_distance_field() {
    $option = (int) get_option('kdquiz_min_distance', 0);
    printf(
        '<input type="number" name="%1$s" value="%2$s" min="0" max="100" step="1" /> %%',
        esc_attr('kdquiz_min_distance'),
        esc_attr($option)
    );
}
