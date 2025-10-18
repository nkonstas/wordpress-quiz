Interactive Quiz 1.2.0

Highlights

- I18n/security: translators comments for placeholder strings; consistent escaping; sanitized/validated AJAX and form inputs with nonce checks.
- Import: replaced direct SQL with WP_Query for duplicate checks; secure admin notice with nonce.
- Performance/VIP: removed post__not_in usage in random fetch; filter viewed IDs in PHP.
- Admin: removed unused admin JS; only admin CSS enqueued.
- Docs: “Tested up to” set to 6.8; README aligned to shortcode and CSS; changelog and upgrade notice added.
- Build: regenerated minified CSS/JS; .gitignore updated to exclude node_modules and tooling artifacts.

Install
- Upload kd-quiz-1.2.0.zip via Plugins → Add New → Upload.

Verify
- Admin: Import JSON, confirm success notice and counts.
- Front end: `[kdquiz]` renders; random questions exclude recently viewed.
- Security: AJAX works only with valid nonce; inputs sanitized.

