=== Interactive Quiz ===
Contributors: nkonstas
Tags: quiz, education, engagement, shortcode, ajax
Requires at least: 5.4
Tested up to: 6.5
Stable tag: 1.1.0
Requires PHP: 7.2
License: GPLv3 or later
License URI: https://github.com/nkonstas/wordpress-quiz/blob/main/LICENSE

Interactive Quiz lets you embed a lightweight, card-style quiz anywhere on your WordPress site to increase visitor engagement without intrusive popups.

== Description ==
Interactive Quiz adds an admin-friendly custom post type for quiz questions, plus a front-end shortcode that renders an animated flash card interface. The plugin includes:

* Four default styles with a custom option for bespoke theming.
* AJAX-powered question rotation, view tracking, and scoring without page reloads.
* Optional automatic insertion based on content headings so you can drop quizzes into long-form articles without shortcodes.
* JSON import/export to seed quizzes in bulk from external systems.

All user inputs are sanitized, escaped, and routed through WordPress nonces to protect your admin users and visitors. No third-party services are contacted.

== Installation ==
1. Upload the `kd-quiz` folder to `wp-content/plugins/` or install it via the WordPress dashboard.
2. Activate **Interactive Quiz** through the **Plugins** menu.
3. Visit **Quiz Questions → Add New Question** to create your first question and mark the correct answer.
4. Configure global behaviour under **Quiz Questions → Settings** (question count, styles, auto-insert rules, and text replacements).
5. Place the `[kd-quiz]` shortcode in any post or page, or enable automatic insertion to let the plugin place quizzes after targeted headings.

== Frequently Asked Questions ==
= How do I change the quiz styling? =
Use **Quiz Questions → Settings** to pick one of the bundled styles or choose **Custom** and enqueue your own CSS targeting `.kd_quiz_style_custom`.

= Can I import questions from another system? =
Yes. Head to **Quiz Questions → Import Questions**, paste a JSON payload with `questionText`, `options`, `correctOptionId`, and `explanation`, then submit the form.

= Does the plugin track personal data? =
No. The plugin stores aggregate view and answer counts per question only.

== Screenshots ==
1. Manage questions and see engagement metrics in the WordPress admin.
2. Configure button text, grades, and auto-insert settings.
3. Import a batch of questions from JSON.
4. Front-end quiz card with score summary.

== Changelog ==
= 1.1.0 =
* Updated option, meta, and function prefixes to the `kdquiz_` namespace to avoid conflicts.
* Hardened nonce checks, request sanitization, and output escaping across AJAX handlers and admin forms.
* Refreshed readme to pass the WordPress.org validator and declared testing up to WordPress 6.5.

== Upgrade Notice ==
= 1.1.0 =
Please clear any cached assets so the updated JavaScript receives the latest option keys and security improvements.
