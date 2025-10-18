Interactive Quiz 1.3.4

Highlights

- Import UX: shows a ready-to-copy JSON sample beneath the textarea so editors know the exact payload shape expected by the importer.
- Settings clarity: adds concise help text under each quiz setting so admins know how auto-insert targeting behaves before saving changes.
- Review log: updates `ReviewFixes.txt` with concise references to nonce, prefix, and migration fixes for the WordPress.org team.
- Build: bumps bundled metadata and recompiles assets for the 1.3.4 package.

Install
- Upload kd-quiz-1.3.4.zip via Plugins → Add New → Upload.

Verify
- Admin: open **Quiz Questions → Import Questions** and confirm the JSON sample renders below the textarea.
- Admin: skim **Quiz Questions → Settings** and verify the new helper copy under each control explains its impact.
- Admin: paste a valid payload, submit, and confirm success/duplicate counts display with the existing notices.
- Front end: `[kdquiz]` shortcode still renders quizzes, handles flips, and records answers without console errors.
