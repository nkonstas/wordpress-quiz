Interactive Quiz 1.3.1

Highlights

- Import UX: invalid or missing JSON now redirects back to the importer with clear, nonce-protected error messaging instead of halting in `admin-post.php`.
- Admin Notices: success and failure banners share a common gate so editors see feedback only on relevant screens.
- Build: regenerated minified CSS/JS bundles for the 1.3.1 tag.

Install
- Upload kd-quiz-1.3.1.zip via Plugins → Add New → Upload.

Verify
- Admin: submit a malformed JSON payload and confirm the import screen shows an error notice.
- Admin: import valid questions; success notice includes imported and duplicate counts.
- Front end: `[kdquiz]` shortcode renders questions and records stats as before.
