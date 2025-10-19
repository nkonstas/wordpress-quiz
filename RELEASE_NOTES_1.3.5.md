Interactive Quiz 1.3.5

Highlights

- Security: escapes the optional `disabled` and `aria-disabled` attributes for auto-insert settings fields so PHPCS security sniffs pass cleanly.
- Documentation: refreshes the q/a checklist in `ReviewFixes.txt`, updates the readme changelog/upgrade notice, and bumps the plugin header to 1.3.5.
- Build: regenerates the compiled JavaScript/CSS bundles so distributed assets advertise version 1.3.5.

Install
- Upload kd-quiz-1.3.5.zip via Plugins → Add New → Upload.

Verify
- Admin: visit **Quiz Questions → Settings** with auto insert disabled and verify the selector fields show the `disabled` state without any unescaped attributes in the page source.
- Admin: re-enable auto insert, toggle the logging checkbox, and confirm the attribute/ARIA state updates without PHPCS warnings.
- Front end: `[kdquiz]` shortcode renders quizzes and records answers, with browser dev tools confirming asset versions report 1.3.5.
