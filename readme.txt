=== KD Quiz – Interactive Quiz ===
Contributors: nkonstas
Tags: quiz, education, engagement, shortcode, ajax
Requires at least: 5.4
Tested up to: 6.8
Stable tag: 1.3.2
Requires PHP: 7.2
License: GPLv3 or later
License URI: https://github.com/nkonstas/wordpress-quiz/blob/main/LICENSE

Embed a lightweight, card-style quiz with AJAX rotation and scoring to boost engagement anywhere on your site.

== Description ==
KD Quiz adds an admin-friendly custom post type for quiz questions, plus a front-end shortcode that renders an animated flash card interface. The plugin includes:

* Four default styles with a custom option for bespoke theming.
* AJAX-powered question rotation, view tracking, and scoring without page reloads.
* Optional automatic insertion based on content headings so you can drop quizzes into long-form articles without shortcodes.
* JSON import/export to seed quizzes in bulk from external systems.

All user inputs are sanitized, escaped, and routed through WordPress nonces to protect your admin users and visitors. No third-party services are contacted.

== Installation ==
1. Upload the `kd-quiz` folder to `wp-content/plugins/` or install it via the WordPress dashboard.
2. Activate **KD Quiz – Interactive Quiz** through the **Plugins** menu.
3. Visit **Quiz Questions → Add New Question** to create your first question and mark the correct answer.
4. Configure global behaviour under **Quiz Questions → Settings** (question count, styles, auto-insert rules, and text replacements).
5. Place the `[kdquiz]` shortcode in any post or page, or enable automatic insertion to let the plugin place quizzes after targeted headings. (The legacy `[kd-quiz]` shortcode continues to work for existing content.)

== Frequently Asked Questions ==
= How do I change the quiz styling? =
Use **Quiz Questions → Settings** to pick one of the bundled styles or choose **Custom** and enqueue your own CSS targeting `.kdquiz_style_custom`.

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
= 1.3.2 =
* Refreshes the Edit Question admin UI with a clear “Mark as correct” pill and row highlight so the chosen answer stands out.
* Adds helper text and status chips to reinforce which answer will be saved as correct.
* Rebuilds admin styles and the bundled JavaScript for the 1.3.2 release package.

= 1.3.1 =
* Improves the JSON import UX by redirecting back to the import screen with contextual error messaging when the payload is missing or invalid.
* Adds nonce-protected status flags so both success and error notices render in the proper admin screens.
* Rebuilds all assets for the 1.3.1 maintenance release.

= 1.3.0 =
* Hardened admin reset/import handlers by routing through `admin-post.php` with nonce and capability checks to satisfy the WordPress.org review.
* Standardised on the `kdquiz` prefix across CPTs, AJAX, styles, and shortcodes while keeping legacy identifiers compatible for existing installs.
* Normalised stored style slugs and added a `[kd-quiz]` shortcode alias so upgrades require no manual content changes.

= 1.2.2 =
* Random fetch fallback to ensure a full question set even when all are marked viewed (keeps VIP-safe query semantics).
* Packaging and i18n: include `/languages` in release; remove discouraged `load_plugin_textdomain()` call.

= 1.2.1 =
* Change plugin name to “KD Quiz – Interactive Quiz” to align with the desired WordPress.org slug `kd-quiz`.
* Maintenance: rebuild minified assets and package script updates.
= 1.2.0 =
* Addressed WordPress Plugin Checker feedback: added translators comments for placeholder strings, ensured proper escaping in admin UI, and sanitized/validated AJAX and form inputs with nonce checks.
* Replaced direct SQL in import duplicate checks with core APIs; tightened import notice handling.
* Removed error_log usage; added `kdquiz_include_failed` hook for observability.
* Updated "Tested up to" to 6.8 and refreshed front-end metadata; minor copy polish in short description.
= 1.1.0 =
* Updated option, meta, and function prefixes to the `kdquiz_` namespace to avoid conflicts.
* Hardened nonce checks, request sanitization, and output escaping across AJAX handlers and admin forms.
* Refreshed readme to pass the WordPress.org validator and declared testing up to WordPress 6.5.

== Upgrade Notice ==
= 1.3.2 =
Update for a clearer “Mark as correct” workflow inside the question editor, plus refreshed admin styling.

= 1.3.1 =
Maintenance release focused on the JSON importer UX: failed imports now return you to the admin screen with clear messaging, plus refreshed assets.

= 1.2.0 =
Compatibility and security hardening release. Please update to ensure translator hints, escaping, nonce verification, and import checks match current WordPress guidelines.
= 1.1.0 =
Please clear any cached assets so the updated JavaScript receives the latest option keys and security improvements.
