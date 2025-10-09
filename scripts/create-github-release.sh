#!/usr/bin/env bash
set -euo pipefail

# Usage:
#   scripts/create-github-release.sh <version>
# or
#   VERSION=1.2.0 scripts/create-github-release.sh

VERSION="${1:-${VERSION:-}}"
if [[ -z "${VERSION}" ]]; then
  # Try to infer from kd-quiz.php header
  if [[ -f "kd-quiz.php" ]]; then
    VERSION=$(grep -E "^\s*\*\s*Version:\s*" kd-quiz.php | head -n1 | sed -E 's/.*Version:\s*([^ ]+).*/\1/') || true
  fi
fi

if [[ -z "${VERSION}" ]] && [[ -f "readme.txt" ]]; then
  VERSION=$(grep -E "^Stable tag:\s*" readme.txt | head -n1 | sed -E 's/.*:\s*([^ ]+).*/\1/') || true
fi

if [[ -z "${VERSION}" ]]; then
  echo "Error: VERSION not provided and could not be inferred from kd-quiz.php or readme.txt" >&2
  echo "Usage: scripts/create-github-release.sh <version>" >&2
  exit 1
fi

TAG="v${VERSION}"
TITLE="Interactive Quiz ${VERSION}"
ASSET="releases/kd-quiz-${VERSION}.zip"
NOTES_FILE="RELEASE_NOTES_${VERSION}.md"

if ! command -v gh >/dev/null 2>&1; then
  echo "Error: GitHub CLI (gh) is not installed."
  echo "Install: https://cli.github.com/ and run 'gh auth login'"
  exit 1
fi

if [ ! -f "$ASSET" ]; then
  echo "Release asset not found at $ASSET. Building it now..."
  if [ -x "./build-release.sh" ]; then
    ./build-release.sh "${VERSION}"
  else
    echo "Error: build-release.sh not found or not executable."
    exit 1
  fi
fi

# Create tag if it doesn't exist
if ! git rev-parse "$TAG" >/dev/null 2>&1; then
  git tag -a "$TAG" -m "Release ${VERSION}"
fi

echo "Pushing tag $TAG to origin..."
git push origin "$TAG"

# Create or update the release
if gh release view "$TAG" >/dev/null 2>&1; then
  echo "Release $TAG exists. Uploading asset (clobber enabled)..."
  gh release upload "$TAG" "$ASSET" --clobber
else
  echo "Creating release $TAG..."
  if [ -f "$NOTES_FILE" ]; then
    gh release create "$TAG" "$ASSET" --title "$TITLE" --notes-file "$NOTES_FILE" --verify-tag
  else
    gh release create "$TAG" "$ASSET" --title "$TITLE" --notes "Release ${VERSION}" --verify-tag
  fi
fi

echo "Done. View release: $(git remote get-url origin | sed 's#git@github.com:#https://github.com/#; s#\.git$##')/releases/tag/$TAG"
