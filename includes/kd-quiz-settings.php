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
// Settings
//

function kdquiz_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Quiz Settings', 'kd-quiz'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('kdquiz_options_group');
            do_settings_sections('kdquiz-settings');
            $text_replacements_url = plugin_dir_url(__FILE__) . '../assets/text-replacements.jpg';
            ?>

            <div class="kdquiz-text-replacements">
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
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('kdquiz_reset_stats_action', 'kdquiz_reset_stats_nonce'); ?>
            <input type="hidden" name="action" value="kdquiz_reset_stats">
            <input type="submit" class="button button-primary kdquiz-action-destructive" value="<?php esc_attr_e('Delete All Question Stats', 'kd-quiz'); ?>" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to reset all quiz stats? This cannot be undone.', 'kd-quiz')); ?>');">
            <p><?php esc_html_e('This will delete all statistics collected for your Quiz Questions. It will not delete any questions.', 'kd-quiz'); ?></p>
        </form>
    </div>
    <?php
}

function kdquiz_register_settings() {
    // Register every surface we expose through the settings UI up front.
    register_setting('kdquiz_options_group', 'kdquiz_number_questions', ['sanitize_callback' => 'absint']);
    register_setting('kdquiz_options_group', 'kdquiz_card_style', [
        'sanitize_callback' => function ($value) {
            return kdquiz_normalize_style_slug($value);
        },
    ]);
    register_setting('kdquiz_options_group', 'kdquiz_enable_auto_insert', [
        'default'           => 0,
        'sanitize_callback' => function ($value) {
            return (int) (bool) $value;
        },
    ]);
    register_setting('kdquiz_options_group', 'kdquiz_insert_before_selectors', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('kdquiz_options_group', 'kdquiz_insert_after_selectors', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('kdquiz_options_group', 'kdquiz_avoid_selectors', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('kdquiz_options_group', 'kdquiz_selector_match', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('kdquiz_options_group', 'kdquiz_container_selector', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('kdquiz_options_group', 'kdquiz_min_distance', ['sanitize_callback' => function ($value) {
        return max(0, absint($value));
    }]);
    register_setting('kdquiz_options_group', 'kdquiz_enable_auto_insert_logging', [
        'default'           => 0,
        'sanitize_callback' => function ($value) {
            return (int) (bool) $value;
        },
    ]);

    add_settings_section(
        'kdquiz_settings_general_section',
        __('General Settings', 'kd-quiz'),
        null,
        'kdquiz-settings'
    );

    add_settings_field(
        'kdquiz_number_questions',
        __('Number of Questions', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_number_questions_field',
        'kdquiz-settings',
        'kdquiz_settings_general_section'
    );

    add_settings_field(
        'kdquiz_card_style',
        __('Card Style', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_card_style_field',
        'kdquiz-settings',
        'kdquiz_settings_general_section'
    );

    add_settings_section(
        'kdquiz_settings_auto_section',
        __('Automatic Placement', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_auto_section_description',
        'kdquiz-settings'
    );

    add_settings_field(
        'kdquiz_enable_auto_insert',
        __('Enable Automatic Quiz Insertion', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_enable_auto_insert_field',
        'kdquiz-settings',
        'kdquiz_settings_auto_section'
    );

    add_settings_field(
        'kdquiz_insert_before_selectors',
        __('Insert Before Selectors', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_insert_before_field',
        'kdquiz-settings',
        'kdquiz_settings_auto_section'
    );

    add_settings_field(
        'kdquiz_insert_after_selectors',
        __('Insert After Selectors', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_insert_after_field',
        'kdquiz-settings',
        'kdquiz_settings_auto_section'
    );

    add_settings_field(
        'kdquiz_avoid_selectors',
        __('Never Insert Inside', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_avoid_selectors_field',
        'kdquiz-settings',
        'kdquiz_settings_auto_section'
    );

    add_settings_field(
        'kdquiz_selector_match',
        __('Selector Match Pattern', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_selector_match_field',
        'kdquiz-settings',
        'kdquiz_settings_auto_section'
    );

    add_settings_field(
        'kdquiz_container_selector',
        __('Container Selector', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_container_selector_field',
        'kdquiz-settings',
        'kdquiz_settings_auto_section'
    );

    add_settings_field(
        'kdquiz_min_distance',
        __('Minimum Distance from Top (px)', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_min_distance_field',
        'kdquiz-settings',
        'kdquiz_settings_auto_section'
    );

    add_settings_field(
        'kdquiz_enable_auto_insert_logging',
        __('Enable Console Logging (Debug)', 'kd-quiz'),
        __NAMESPACE__ . '\\kdquiz_auto_logging_field',
        'kdquiz-settings',
        'kdquiz_settings_auto_section'
    );

    add_settings_section(
        'kdquiz_settings_text_section',
        __('Text Replacements', 'kd-quiz'),
        null,
        'kdquiz-settings'
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
            'kdquiz-settings',
            'kdquiz_settings_text_section'
        );
    }

}

function kdquiz_migrate_style_option() {
    $current = get_option('kdquiz_card_style', '');
    if (is_string($current) && strpos($current, 'kd_quiz_style_') === 0) {
        update_option('kdquiz_card_style', kdquiz_normalize_style_slug($current));
    }
}

add_action('admin_init', __NAMESPACE__ . '\\kdquiz_migrate_style_option', 5);
add_action('admin_init', __NAMESPACE__ . '\\kdquiz_migrate_min_distance_option', 6);
add_action('admin_init', __NAMESPACE__ . '\\kdquiz_migrate_heading_selector_option', 7);
add_action('admin_init', __NAMESPACE__ . '\\kdquiz_register_settings');
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\kdquiz_enqueue_settings_admin_assets');

function kdquiz_handle_reset_stats() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'kd-quiz'));
    }

    check_admin_referer('kdquiz_reset_stats_action', 'kdquiz_reset_stats_nonce');

    // Nukes the counters so editors can start A/B tests fresh.
    kdquiz_reset_all_stats();

    $redirect_url = add_query_arg(
        [
            'page'                    => 'kdquiz-settings',
            'kdquiz_reset_stats_done' => 1,
            'kdquiz_reset_stats_notice_nonce' => wp_create_nonce('kdquiz_reset_notice'),
        ],
        admin_url('admin.php')
    );

    wp_safe_redirect($redirect_url);
    exit;
}

add_action('admin_post_kdquiz_reset_stats', __NAMESPACE__ . '\\kdquiz_handle_reset_stats');

function kdquiz_maybe_render_reset_notice() {
    if (!isset($_GET['kdquiz_reset_stats_done'], $_GET['kdquiz_reset_stats_notice_nonce'])) {
        return;
    }

    $notice_nonce = sanitize_text_field(wp_unslash($_GET['kdquiz_reset_stats_notice_nonce']));
    if (!wp_verify_nonce($notice_nonce, 'kdquiz_reset_notice')) {
        return;
    }

    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ('kdquiz-settings' !== $page) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Quiz stats have been reset.', 'kd-quiz') . '</p></div>';
}

add_action('admin_notices', __NAMESPACE__ . '\\kdquiz_maybe_render_reset_notice');

function kdquiz_reset_all_stats() {
    $questions = get_posts([
        'post_type'      => ['kdquiz_question', 'kd_quiz_question'],
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
    echo '<p class="description">' . esc_html__('Controls how many questions the front-end fetches per quiz rotation. Increasing this raises the pool pulled from the question CPT.', 'kd-quiz') . '</p>';
}

function kdquiz_card_style_field() {
    $styles = kdquiz_get_quiz_styles();
    $current_value = kdquiz_normalize_style_slug(get_option('kdquiz_card_style', 'kdquiz_style_1'));

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
    echo '<p class="description">' . esc_html__('Selects the CSS theme class applied to quiz cards. Custom styles should enqueue their own rules targeting the chosen slug.', 'kd-quiz') . '</p>';
}

function kdquiz_enable_auto_insert_field() {
    $option = (int) get_option('kdquiz_enable_auto_insert', 0);
    echo '<input type="hidden" name="kdquiz_enable_auto_insert" value="0">';
    printf(
        '<input type="checkbox" name="%1$s" value="1" %2$s />',
        esc_attr('kdquiz_enable_auto_insert'),
        checked(1, $option, false)
    );
    echo '<p class="description">' . esc_html__('When enabled, the plugin injects a quiz container automatically inside the selected content container instead of waiting for the `[kdquiz]` shortcode. Additional placement controls unlock once this is checked.', 'kd-quiz') . '</p>';
}
function kdquiz_auto_section_description() {
    $message = __('Automatically insert the quiz inside your content wrapper using the selectors below. “Insert before” runs first, followed by “insert after”, while “never insert inside” skips entire sections.', 'kd-quiz');
    echo '<p>' . esc_html($message) . '</p>';
}

function kdquiz_insert_before_field() {
    $option  = get_option('kdquiz_insert_before_selectors', 'h2, h3');
    $enabled = (int) get_option('kdquiz_enable_auto_insert', 0) === 1;
    printf(
        '<input type="text" class="regular-text kdquiz-auto-setting" name="%1$s" value="%2$s"%3$s%4$s placeholder="%5$s" />',
        esc_attr('kdquiz_insert_before_selectors'),
        esc_attr($option),
        disabled(!$enabled, true, false),
        $enabled ? '' : sprintf(' aria-disabled="%s"', esc_attr('true')),
        esc_attr('h2, .lead-heading')
    );
    $base_message = __('Comma-separated selectors that should receive the quiz directly before the first match (evaluated depth-first).', 'kd-quiz');
    $disabled_note = __('Enable automatic insertion above to edit this field.', 'kd-quiz');
    $display_message = $enabled ? $base_message : $base_message . ' ' . $disabled_note;
    printf(
        '<p class="description kdquiz-auto-description" data-base="%1$s" data-disabled-note="%2$s">%3$s</p>',
        esc_attr($base_message),
        esc_attr($disabled_note),
        esc_html($display_message)
    );
}

function kdquiz_insert_after_field() {
    $option  = get_option('kdquiz_insert_after_selectors', '');
    $enabled = (int) get_option('kdquiz_enable_auto_insert', 0) === 1;
    printf(
        '<input type="text" class="regular-text kdquiz-auto-setting" name="%1$s" value="%2$s"%3$s%4$s placeholder="%5$s" />',
        esc_attr('kdquiz_insert_after_selectors'),
        esc_attr($option),
        disabled(!$enabled, true, false),
        $enabled ? '' : sprintf(' aria-disabled="%s"', esc_attr('true')),
        esc_attr('.summary + h3')
    );
    $base_message = __('Selectors that should receive the quiz immediately after the first match (only used if no “insert before” match exists).', 'kd-quiz');
    $disabled_note = __('Enable automatic insertion above to edit this field.', 'kd-quiz');
    $display_message = $enabled ? $base_message : $base_message . ' ' . $disabled_note;
    printf(
        '<p class="description kdquiz-auto-description" data-base="%1$s" data-disabled-note="%2$s">%3$s</p>',
        esc_attr($base_message),
        esc_attr($disabled_note),
        esc_html($display_message)
    );
}

function kdquiz_avoid_selectors_field() {
    $option  = get_option('kdquiz_avoid_selectors', '.site-footer, .site-sidebar');
    $enabled = (int) get_option('kdquiz_enable_auto_insert', 0) === 1;
    printf(
        '<input type="text" class="regular-text kdquiz-auto-setting" name="%1$s" value="%2$s"%3$s%4$s placeholder="%5$s" />',
        esc_attr('kdquiz_avoid_selectors'),
        esc_attr($option),
        disabled(!$enabled, true, false),
        $enabled ? '' : sprintf(' aria-disabled="%s"', esc_attr('true')),
        esc_attr('.footer, .widget')
    );
    $base_message = __('Skip any matching containers and their children (useful for footers, sidebars, or other excluded regions).', 'kd-quiz');
    $disabled_note = __('Enable automatic insertion above to edit this field.', 'kd-quiz');
    $display_message = $enabled ? $base_message : $base_message . ' ' . $disabled_note;
    printf(
        '<p class="description kdquiz-auto-description" data-base="%1$s" data-disabled-note="%2$s">%3$s</p>',
        esc_attr($base_message),
        esc_attr($disabled_note),
        esc_html($display_message)
    );
}

function kdquiz_selector_match_field() {
    $option = get_option('kdquiz_selector_match', '');
    $enabled = (int) get_option('kdquiz_enable_auto_insert', 0) === 1;
    printf(
        '<input type="text" class="regular-text kdquiz-auto-setting" name="%1$s" value="%2$s"%3$s%4$s />',
        esc_attr('kdquiz_selector_match'),
        esc_attr($option),
        disabled(!$enabled, true, false),
        $enabled ? '' : sprintf(' aria-disabled="%s"', esc_attr('true'))
    );
    $base_message = __('Optional text filter for matched elements; supports `*` wildcards (e.g. `*Quiz*`) and applies to both before/after selectors.', 'kd-quiz');
    $disabled_note = __('Enable automatic insertion above to edit this field.', 'kd-quiz');
    $display_message = $enabled ? $base_message : $base_message . ' ' . $disabled_note;
    printf(
        '<p class="description kdquiz-auto-description" data-base="%1$s" data-disabled-note="%2$s">%3$s</p>',
        esc_attr($base_message),
        esc_attr($disabled_note),
        esc_html($display_message)
    );
}

function kdquiz_container_selector_field() {
    $option = get_option('kdquiz_container_selector', '.entry-content, .post-content, main');
    $enabled = (int) get_option('kdquiz_enable_auto_insert', 0) === 1;
    printf(
        '<input type="text" name="%1$s" value="%2$s" class="large-text kdquiz-auto-setting"%3$s%4$s />',
        esc_attr('kdquiz_container_selector'),
        esc_attr($option),
        disabled(!$enabled, true, false),
        $enabled ? '' : sprintf(' aria-disabled="%s"', esc_attr('true'))
    );
    $base_message = __('Limits auto insertion to specific content wrappers (comma-separated selectors, e.g. `.entry-content, .post-content, main`). Leave blank to scan the entire document.', 'kd-quiz');
    $disabled_note = __('Enable automatic insertion above to edit this field.', 'kd-quiz');
    $display_message = $enabled ? $base_message : $base_message . ' ' . $disabled_note;
    printf(
        '<p class="description kdquiz-auto-description" data-base="%1$s" data-disabled-note="%2$s">%3$s</p>',
        esc_attr($base_message),
        esc_attr($disabled_note),
        esc_html($display_message)
    );
}

function kdquiz_min_distance_field() {
    $option = (int) get_option('kdquiz_min_distance', 0);
    $enabled = (int) get_option('kdquiz_enable_auto_insert', 0) === 1;
    printf(
        '<input type="number" class="kdquiz-auto-setting" name="%1$s" value="%2$s" min="0" step="1"%3$s%4$s /> px',
        esc_attr('kdquiz_min_distance'),
        esc_attr($option),
        disabled(!$enabled, true, false),
        $enabled ? '' : sprintf(' aria-disabled="%s"', esc_attr('true'))
    );
    $base_message = __('Minimum scroll threshold in pixels before the auto-inserted quiz renders. Set to 0 to allow insertion near the top of the container.', 'kd-quiz');
    $disabled_note = __('Enable automatic insertion above to edit this field.', 'kd-quiz');
    $display_message = $enabled ? $base_message : $base_message . ' ' . $disabled_note;
    printf(
        '<p class="description kdquiz-auto-description" data-base="%1$s" data-disabled-note="%2$s">%3$s</p>',
        esc_attr($base_message),
        esc_attr($disabled_note),
        esc_html($display_message)
    );
}

function kdquiz_auto_logging_field() {
    $enabled = (int) get_option('kdquiz_enable_auto_insert', 0) === 1;
    $logging_enabled = (int) get_option('kdquiz_enable_auto_insert_logging', 0);
    echo '<input type="hidden" name="kdquiz_enable_auto_insert_logging" value="0">';
    printf(
        '<label><input type="checkbox" class="kdquiz-auto-setting" name="%1$s" value="1"%2$s%3$s%4$s /> %5$s</label>',
        esc_attr('kdquiz_enable_auto_insert_logging'),
        checked(1, $logging_enabled, false),
        disabled(!$enabled, true, false),
        $enabled ? '' : sprintf(' aria-disabled="%s"', esc_attr('true')),
        esc_html__('Log auto-placement decisions to the browser console for debugging.', 'kd-quiz')
    );
    $base_message = __('When enabled, the quiz auto-placer prints detailed heading and offset diagnostics to the browser console. Disable once you finish debugging.', 'kd-quiz');
    $disabled_note = __('Enable automatic insertion above to adjust this setting.', 'kd-quiz');
    $display_message = $enabled ? $base_message : $base_message . ' ' . $disabled_note;
    printf(
        '<p class="description kdquiz-auto-description" data-base="%1$s" data-disabled-note="%2$s">%3$s</p>',
        esc_attr($base_message),
        esc_attr($disabled_note),
        esc_html($display_message)
    );
}
function kdquiz_migrate_min_distance_option() {
    if (get_option('kdquiz_min_distance_migrated')) {
        return;
    }

    $existing = get_option('kdquiz_min_distance', null);
    if (null === $existing) {
        update_option('kdquiz_min_distance_migrated', 1);
        return;
    }

    $existing = absint($existing);

    if ($existing <= 100) {
        // Assume legacy percent value and approximate one viewport as ~8px per percent (≈800px @100%).
        $approx_pixels = (int) round($existing * 8);
        update_option('kdquiz_min_distance', $approx_pixels);
    } else {
        update_option('kdquiz_min_distance', $existing);
    }

    update_option('kdquiz_min_distance_migrated', 1);
}

function kdquiz_migrate_heading_selector_option() {
    if (get_option('kdquiz_heading_selector_migrated')) {
        return;
    }

    $legacy_selectors = get_option('kdquiz_heading_selector', null);
    if (null !== $legacy_selectors && '' !== $legacy_selectors) {
        if ('' === get_option('kdquiz_insert_before_selectors', '')) {
            update_option('kdquiz_insert_before_selectors', sanitize_text_field($legacy_selectors));
        }
    }

    update_option('kdquiz_heading_selector_migrated', 1);
}

function kdquiz_enqueue_settings_admin_assets() {
    if (!function_exists('get_current_screen')) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || 'kdquiz_question_page_kdquiz-settings' !== $screen->id) {
        return;
    }

    $script_path = '../assets/kd-admin-quiz.min.js';
    $script_full_path = plugin_dir_path(__FILE__) . $script_path;
    $script_url = plugins_url($script_path, __FILE__);
    $version = file_exists($script_full_path) ? (string) filemtime($script_full_path) : false;

    wp_enqueue_script('kdquiz-admin-settings', $script_url, [], $version ?: null, true);
}
