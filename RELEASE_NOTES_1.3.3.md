Interactive Quiz 1.3.3

Highlights

- Compliance polish: escapes the answer status pill markup and adds nonce verification to the reset notice to satisfy WordPress.org review feedback.
- Migration safety: replaces the direct SQL post-type migration with `wp_update_post()` so installs running object caches stay in sync.
- Packaging: updates embedded metadata and rebuilds the distributable for the 1.3.3 tag.

Install
- Upload kd-quiz-1.3.3.zip via Plugins → Add New → Upload.

Verify
- Admin: trigger **Reset Stats** and confirm the success notice still appears after the redirect.
- Admin: open a question and ensure the “Correct choice” pill renders with the expected status text.
- Front end: `[kdquiz]` shortcode continues to render quizzes, flip cards, and record responses without console errors.
