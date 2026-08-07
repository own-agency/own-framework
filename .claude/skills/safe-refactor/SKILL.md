---
name: safe-refactor
description: Verified comment cleanup, file renames or code moves in the own-framework theme — proves the change altered nothing executable. Use when asked to trim comments, rename files, reorganise folders or tidy code without changing behaviour.
disable-model-invocation: true
allowed-tools: Read, Write, Edit, Bash, Glob, Grep
---

Behaviour-preserving changes to this theme: comment cleanup, file renames, moving
code. The point of this skill is that "I only changed comments" is *proved*, not
asserted — this is a live client site with no test suite, so the verification is
the safety net.

Read `CLAUDE.md` first for the comment standard and the never-rename list.

---

## 1. Snapshot a code-only baseline first

`php -w` strips PHP comments and whitespace. Hashing its output fingerprints
**code only**, so an identical hash afterwards proves nothing executable moved.

```sh
find . -name "*.php" -not -path "./node_modules/*" | sort | while read -r f; do
  echo "$(php -w "$f" 2>/dev/null | tr -d '[:space:]' | shasum | cut -d' ' -f1)  $f"
done > /tmp/code-baseline.txt
```

Take this **before** touching anything. If the task also includes renames, do the
renames first, then re-snapshot — a rename legitimately changes path strings, so
it needs its own baseline.

## 2. Make the change

For comments, apply the Keep/Cut rules in `CLAUDE.md`. Roughly halve the verbose
files; leave already-concise files completely alone. Never pad or reword for its
own sake.

## 3. Prove it

```sh
while read -r want f; do
  [ -f "$f" ] || { echo "MISSING: $f"; continue; }
  now=$(php -w "$f" 2>/dev/null | tr -d '[:space:]' | shasum | cut -d' ' -f1)
  [ "$want" = "$now" ] || echo "CHANGED: $f"
done < /tmp/code-baseline.txt
```

Any file listed as CHANGED means you altered code. Investigate every one — do not
wave it through.

## Four ways this check misleads you

These are real failures seen in practice, not hypotheticals:

1. **Comments inside a PHP string are code.** The JS in `wp_add_inline_script()`
   (`functions.php`) and heredocs are string literals — `php -w` keeps them, so
   editing them shows up as CHANGED. Correct response: revert, and leave
   string-embedded comments alone.

2. **`php -w` preserves inline HTML.** Files that `echo` a `<style>` or
   `<script>` block (several in `inc/scf/`) hold CSS/JS comments that the hash
   can't see. If you trim those, verify differently:
   - strip `/* … */` from both versions and compare, and
   - extract each `<script>` body and run `node --check` on it.
   Reflowing a `//` comment in JS can swallow the next statement — the syntax
   check is what catches that.

3. **zsh: never `read` into a variable named `path`.** It is tied to `$PATH`;
   assigning it breaks every command in the loop and reports *every* file as
   changed. Use `f`, `file`, anything else.

4. **A naive "is this line a comment?" regex is not enough.** Continuation lines
   inside a `/* … */` block don't start with `*`, so they look like code. Prefer
   the hash comparison over line-pattern matching.

## 4. Then the normal gates

```sh
# every PHP file parses
find . -name "*.php" -not -path "./node_modules/*" -exec php -l {} \; | grep -v "No syntax errors"

# no get_template_part() points at a missing file
# (a dynamically-built base, e.g. `"template-parts/shapes/$variant"`, is a
# legitimate false positive here — check the surrounding code before assuming
# a match is actually broken)
grep -rhoE "get_template_part\(\s*'[^']+'" --include="*.php" . | grep -v node_modules \
  | sed "s/.*'\(.*\)'/\1/" | sort -u | while read -r p; do [ -f "$p.php" ] || echo "unresolved: $p"; done

pnpm build
```

After a rename, also grep for stale references in **comments and CSS**, not just
code — this theme cross-references file paths in both:

```sh
grep -rn "<old-filename>" --include="*.php" --include="*.css" --include="*.js" --include="*.md" . | grep -v node_modules
```

## Renaming: check it is allowed first

`CLAUDE.md` lists names that are stored data (block names, block folders, ACF
field names, group keys, post type and taxonomy slugs). Those are off-limits —
renaming them orphans live content. Only `template-parts/`, `inc/`, `src/` and
`media/` are freely renameable.

Rename only when the current name is actively misleading. "Already correct" is a
valid outcome — leave it.

## Report honestly

State comment lines before → after per file, and the verification result. If a
hash mismatch turned out to be a real code change you reverted, say so. If you
could not verify something, say that too — an unverified claim is worse than a
flagged gap.
