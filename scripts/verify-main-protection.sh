#!/usr/bin/env bash
#
# Verifies that the branch protection on `main` (the "Protect main" ruleset)
# is active and enforced by GitHub.
#
# Strategy: from a throwaway branch that commits on top of remote `main`,
# attempt a DIRECT (non-force, fast-forward) push onto `main`. The ruleset
# requires changes to go through a pull request, so GitHub must reject the
# direct push.
#
#   push rejected  -> protection is working (exit 0)
#   push succeeds  -> protection is NOT configured (exit 1)
#
# Usage:
#   scripts/verify-main-protection.sh                     # uses the local git remote
#   PROTECTION_REPO=org/repo scripts/verify-main-protection.sh

set -euo pipefail

REPO="${PROTECTION_REPO:-}"
BRANCH="protection-test-$(date +%s)"
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

info() { printf '\n\033[1m%s\033[0m\n' "$*"; }
fail() { printf '\033[31m%s\033[0m\n' "$*"; }
ok()   { printf '\033[32m%s\033[0m\n' "$*"; }

if [[ -z "$REPO" ]]; then
    REPO=$(git config --get remote.origin.url 2>/dev/null || true)
    REPO=${REPO#git@github.com:}
    REPO=${REPO%.git}
    REPO=${REPO#https://github.com/}
fi

if [[ -z "$REPO" ]]; then
    fail 'Could not determine the repository. Set PROTECTION_REPO=org/repo.'
    exit 1
fi

info "[1/4] Cloning $REPO"
git clone --quiet --branch main --single-branch "git@github.com:${REPO}.git" "$TMP"
cd "$TMP"

info "[2/4] Creating throwaway commit on $BRANCH"
git checkout -q -b "$BRANCH"
printf 'branch-protection probe %s\n' "$(date -u +%FT%TZ)" > PROTECTION_PROBE
git add PROTECTION_PROBE
git -c user.name='Protection Test' -c user.email='protection@test.local' \
    commit -q -m 'ci(probe): verify main branch protection rejects direct pushes'

info '[3/4] Attempting DIRECT (non-force) push to main...'
if git push origin "$BRANCH:main" 2> push.err; then
    fail '[4/4] FAIL: the direct push to main SUCCEEDED -> protection is NOT working'
    exit 1
fi

info '[4/4] Push rejected by GitHub:'
sed 's/^/    /' push.err
ok 'OK: direct push to main was refused -> branch protection is working'
