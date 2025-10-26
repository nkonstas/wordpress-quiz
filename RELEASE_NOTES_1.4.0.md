Interactive Quiz 1.4.0

Highlights

- Admin: replaces the inline “mark as correct” helper with a dedicated script that loads via `wp_enqueue_script()` so the question editor complies with WordPress asset guidelines.
- Build: adds a minified variant of the new admin helper and updates the npm build chain to regenerate it with every release.
- Tooling: teaches `build-release.sh` to install npm dependencies when needed, run `npm run build`, and package the freshly compiled assets into the distributable ZIP.

Install
- Upload kd-quiz-1.4.0.zip via Plugins → Add New → Upload.

Verify
- Admin: edit a **Quiz Question** and confirm the answer rows highlight when you change the “Mark as correct” selection, with the console showing no inline-script warnings.
- Admin: reload **Quiz Questions → Settings** and confirm the stylesheet still applies (shows `.kdquiz-text-replacements` layout).
- Build: run `./build-release.sh 1.4.0` locally and verify the script installs dependencies (if missing), runs the npm build, and produces `releases/kd-quiz-1.4.0.zip`.
