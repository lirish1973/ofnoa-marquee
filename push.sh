#!/usr/bin/env bash
#
# push.sh — commit, tag, push and release Ofnoa Marquee.
#
# Works in Git Bash on Windows, WSL and Linux/macOS.
#
#   ./push.sh                                  push what is already committed
#   ./push.sh -m "fix: separator spacing"      commit everything, then push
#   ./push.sh -z -t -r                         build the ZIP, tag v<version>, publish the release
#
# Options:
#   -m, --message MSG   commit all pending changes with this message first
#   -z, --zip           rebuild ofnoa-marquee.zip (nested correctly, no dev files)
#   -t, --tag           create and push the tag v<version> from the plugin header
#   -r, --release       publish a GitHub release for that tag with the ZIP (needs gh)
#       --remote NAME   default: origin
#       --branch NAME   default: the current branch
#   -n, --dry-run       show what would happen, change nothing
#   -h, --help          this text

set -uo pipefail

# ---------------------------------------------------------------- output

if [ -t 1 ]; then
	C_STEP=$'\033[36m'; C_OK=$'\033[32m'; C_WARN=$'\033[33m'; C_ERR=$'\033[31m'; C_DIM=$'\033[90m'; C_OFF=$'\033[0m'
else
	C_STEP=''; C_OK=''; C_WARN=''; C_ERR=''; C_DIM=''; C_OFF=''
fi

step() { printf '\n%s==> %s%s\n' "$C_STEP" "$1" "$C_OFF"; }
good() { printf '%s    %s%s\n' "$C_OK" "$1" "$C_OFF"; }
note() { printf '%s    %s%s\n' "$C_DIM" "$1" "$C_OFF"; }
warn() { printf '%s    %s%s\n' "$C_WARN" "$1" "$C_OFF"; }
die()  { printf '\n%sFAILED: %s%s\n' "$C_ERR" "$1" "$C_OFF" >&2; exit 1; }
have() { command -v "$1" >/dev/null 2>&1; }

usage() { awk 'NR==1 { next } /^#/ { sub(/^#[[:space:]]?/, ""); print; next } { exit }' "$0"; exit 0; }

# ---------------------------------------------------------------- arguments

MESSAGE=""
DO_ZIP=0
DO_TAG=0
DO_RELEASE=0
DRY_RUN=0
REMOTE="origin"
BRANCH=""

while [ $# -gt 0 ]; do
	case "$1" in
		-m|--message) MESSAGE="${2:-}"; [ -n "$MESSAGE" ] || die "-m needs a message."; shift 2 ;;
		-z|--zip)     DO_ZIP=1; shift ;;
		-t|--tag)     DO_TAG=1; shift ;;
		-r|--release) DO_RELEASE=1; shift ;;
		-n|--dry-run) DRY_RUN=1; shift ;;
		--remote)     REMOTE="${2:-}"; [ -n "$REMOTE" ] || die "--remote needs a name."; shift 2 ;;
		--branch)     BRANCH="${2:-}"; [ -n "$BRANCH" ] || die "--branch needs a name."; shift 2 ;;
		-h|--help)    usage ;;
		*)            die "Unknown option: $1  (try --help)" ;;
	esac
done

run() {
	if [ "$DRY_RUN" -eq 1 ]; then
		note "would run: $*"
		return 0
	fi
	"$@"
}

# ---------------------------------------------------------------- sanity

cd "$(dirname "$0")" || die "Cannot enter the script directory."

have git || die "git is not on PATH."
[ -f ofnoa-marquee.php ] || die "Run this from the plugin folder (ofnoa-marquee.php not found)."
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || die "This folder is not a git repository."

[ "$DRY_RUN" -eq 1 ] && warn "Dry run — nothing will be committed, pushed or published."

# ---------------------------------------------------------------- version

step "Reading version"

VERSION=$(sed -n 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*\([0-9][0-9.]*\).*/\1/p' ofnoa-marquee.php | head -1)
CONST_VERSION=$(sed -n "s/.*OMQ_VERSION',[[:space:]]*'\([0-9][0-9.]*\)'.*/\1/p" ofnoa-marquee.php | head -1)

[ -n "$VERSION" ]       || die "No 'Version:' line in the plugin header."
[ -n "$CONST_VERSION" ] || die "OMQ_VERSION constant not found."

if [ "$VERSION" != "$CONST_VERSION" ]; then
	die "Version mismatch: header says $VERSION but OMQ_VERSION is $CONST_VERSION. Fix both, then run again."
fi

if [ -f readme.txt ]; then
	STABLE=$(sed -n 's/^Stable tag:[[:space:]]*\([0-9][0-9.]*\).*/\1/p' readme.txt | head -1)
	if [ -n "$STABLE" ] && [ "$STABLE" != "$VERSION" ]; then
		warn "readme.txt Stable tag is $STABLE, plugin is $VERSION."
	fi
fi

good "version $VERSION"

# ---------------------------------------------------------------- branch

CURRENT=$(git rev-parse --abbrev-ref HEAD)
if [ -z "$BRANCH" ]; then
	BRANCH="$CURRENT"
elif [ "$BRANCH" != "$CURRENT" ]; then
	warn "You are on '$CURRENT' but asked to push '$BRANCH'."
fi

# ---------------------------------------------------------------- commit

DIRTY=$(git status --porcelain)

if [ -n "$MESSAGE" ]; then
	if [ -z "$DIRTY" ]; then
		note "Nothing to commit, working tree is clean."
	else
		step "Committing"
		run git add -A || die "git add failed."
		run git commit -m "$MESSAGE" || die "git commit failed."
		[ "$DRY_RUN" -eq 1 ] || good "$(git log --oneline -1)"
	fi
elif [ -n "$DIRTY" ]; then
	printf '\n'
	warn "Uncommitted changes:"
	git status --short
	die "Commit them first, or pass -m \"your message\"."
fi

# ---------------------------------------------------------------- zip

make_zip() {
	# Build ofnoa-marquee.zip from the tracked files, nested inside ofnoa-marquee/.
	#
	# Everything happens with RELATIVE paths inside a staging directory, because
	# Git Bash hands MSYS paths (/f/...) to native Windows tools, which cannot
	# read them. Relative paths inherit the working directory instead, so the
	# same code works for msys `zip`, a native python.exe, PowerShell and bsdtar.
	local staging src zip_path out method count py
	zip_path="$PWD/ofnoa-marquee.zip"
	staging=$(mktemp -d 2>/dev/null || mktemp -d -t omq)
	src="$staging/src"
	out="$staging/out.zip"
	mkdir -p "$src/ofnoa-marquee"

	# git ls-files is the source of truth: whatever .gitignore already excludes
	# can never sneak into a release, and dev-only files are filtered here.
	git ls-files \
		| grep -vE '^(\.github/|\.gitignore$|push\.(ps1|bat|sh)$|ofnoa-marquee\.zip$)' \
		| while IFS= read -r f; do
			mkdir -p "$src/ofnoa-marquee/$(dirname "$f")"
			cp "$f" "$src/ofnoa-marquee/$f"
		done

	count=$(find "$src" -type f | wc -l | tr -d ' ')
	[ "$count" -gt 0 ] || { rm -rf "$staging"; die "Nothing to package."; }

	method=""

	# 1. Real zip binary.
	if have zip; then
		rm -f "$out"
		( cd "$src" && zip -qr ../out.zip ofnoa-marquee ) >/dev/null 2>&1
		[ -s "$out" ] && method="zip"
	fi

	# 2. Python — but only a real interpreter. On Windows, python3/python are
	#    often App Execution Alias stubs that print a banner and create nothing,
	#    so probe before trusting them.
	if [ -z "$method" ]; then
		for py in python3 python py; do
			have "$py" || continue
			[ "$("$py" -c 'print(42)' 2>/dev/null | tr -d '\r')" = "42" ] || continue
			rm -f "$out"
			( cd "$src" && "$py" -c "import shutil; shutil.make_archive('../out','zip','.','ofnoa-marquee')" ) >/dev/null 2>&1
			if [ -s "$out" ]; then method="$py"; break; fi
		done
	fi

	# 3. PowerShell (always present on Windows).
	if [ -z "$method" ] && have powershell.exe; then
		rm -f "$out"
		( cd "$staging" && powershell.exe -NoProfile -NonInteractive -Command \
			"Add-Type -AssemblyName System.IO.Compression.FileSystem; [System.IO.Compression.ZipFile]::CreateFromDirectory((Join-Path (Get-Location).Path 'src'), (Join-Path (Get-Location).Path 'out.zip'), [System.IO.Compression.CompressionLevel]::Optimal, \$false)" \
		) >/dev/null 2>&1
		[ -s "$out" ] && method="powershell"
	fi

	# 4. Windows' own bsdtar, which writes zip when the extension says so.
	if [ -z "$method" ] && [ -x /c/Windows/System32/tar.exe ]; then
		rm -f "$out"
		( cd "$src" && /c/Windows/System32/tar.exe -a -c -f ../out.zip ofnoa-marquee ) >/dev/null 2>&1
		[ -s "$out" ] && method="bsdtar"
	fi

	if [ -z "$method" ]; then
		rm -rf "$staging"
		die "No working ZIP tool found (tried zip, python, PowerShell, bsdtar). Install 'zip', or run push.ps1 instead."
	fi

	rm -f "$zip_path"
	mv "$out" "$zip_path" || { rm -rf "$staging"; die "Could not move the archive into place."; }
	rm -rf "$staging"

	good "ofnoa-marquee.zip ($count files, $(du -k "$zip_path" | cut -f1) KB, via $method)"
}

if [ "$DO_ZIP" -eq 1 ]; then
	step "Building ofnoa-marquee.zip"
	if [ "$DRY_RUN" -eq 1 ]; then
		note "would package $(git ls-files | grep -cvE '^(\.github/|\.gitignore$|push\.(ps1|bat|sh)$|ofnoa-marquee\.zip$)') files"
	else
		make_zip
	fi
fi

# ---------------------------------------------------------------- push

step "Pushing $BRANCH to $REMOTE"

if git rev-parse --verify --quiet "refs/remotes/$REMOTE/$BRANCH" >/dev/null 2>&1; then
	AHEAD=$(git log --oneline "$REMOTE/$BRANCH..HEAD")
	if [ -n "$AHEAD" ]; then
		printf '%s\n' "$AHEAD" | while IFS= read -r line; do note "$line"; done
	else
		note "Nothing new — remote is already up to date."
	fi
else
	note "No remote-tracking branch yet — this will create $REMOTE/$BRANCH."
fi

if [ "$DRY_RUN" -eq 1 ]; then
	note "would run: git push $REMOTE $BRANCH"
else
	PUSH_OUT=$(git push "$REMOTE" "$BRANCH" 2>&1)
	PUSH_RC=$?
	printf '%s\n' "$PUSH_OUT" | while IFS= read -r line; do note "$line"; done

	if [ $PUSH_RC -ne 0 ]; then
		case "$PUSH_OUT" in
			*workflow*)
				printf '\n'
				warn "GitHub refused the push because your token lacks the 'workflow' scope"
				warn "(needed to create or change files under .github/workflows/)."
				warn "Fix it once with:   gh auth refresh -h github.com -s workflow"
				;;
			*"could not read Username"*|*"Authentication failed"*)
				printf '\n'
				warn "No GitHub credentials here. Sign in with:   gh auth login"
				warn "Or store them once:   git config credential.helper store"
				;;
			*"rejected"*|*"non-fast-forward"*)
				printf '\n'
				warn "The remote has commits you do not. Run:   git pull --rebase $REMOTE $BRANCH"
				;;
		esac
		die "git push failed."
	fi
	good "branch pushed"
fi

# ---------------------------------------------------------------- tag

if [ "$DO_TAG" -eq 1 ]; then
	step "Tagging v$VERSION"

	if git rev-parse -q --verify "refs/tags/v$VERSION" >/dev/null; then
		note "Local tag v$VERSION already exists."
	else
		run git tag "v$VERSION" || die "Could not create the tag."
		[ "$DRY_RUN" -eq 1 ] || good "created v$VERSION"
	fi

	if [ "$DRY_RUN" -eq 1 ]; then
		note "would run: git push $REMOTE v$VERSION"
	else
		TAG_OUT=$(git push "$REMOTE" "v$VERSION" 2>&1)
		TAG_RC=$?
		printf '%s\n' "$TAG_OUT" | while IFS= read -r line; do note "$line"; done
		if [ $TAG_RC -ne 0 ]; then
			warn "If the tag already exists on GitHub, replace it with:"
			warn "  git push --delete $REMOTE v$VERSION && git push $REMOTE v$VERSION"
			die "Could not push the tag."
		fi
		good "tag pushed"
	fi
fi

# ---------------------------------------------------------------- release

if [ "$DO_RELEASE" -eq 1 ]; then
	step "Publishing the GitHub release"

	have gh || die "gh (GitHub CLI) is not installed — create the release manually, or get it from https://cli.github.com"
	[ -f ofnoa-marquee.zip ] || die "ofnoa-marquee.zip not found. Run again with -z."

	if [ "$DRY_RUN" -eq 1 ]; then
		note "would run: gh release create v$VERSION ofnoa-marquee.zip --generate-notes"
	elif gh release view "v$VERSION" >/dev/null 2>&1; then
		note "Release v$VERSION already exists — uploading the ZIP over it."
		gh release upload "v$VERSION" ofnoa-marquee.zip --clobber || die "gh release upload failed."
		good "release v$VERSION updated"
	else
		gh release create "v$VERSION" ofnoa-marquee.zip --title "v$VERSION" --generate-notes || die "gh release create failed."
		good "release v$VERSION is live"
	fi
fi

printf '\n%sDone — Ofnoa Marquee %s is on GitHub.%s\n\n' "$C_OK" "$VERSION" "$C_OFF"
