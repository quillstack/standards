---
name: quillstack-package
description: The shape every Quillstack package takes, in whatever language it is written — README sections, badges, the manifest, repository metadata, the documentation page, and how a release is verified and tagged. Use when creating a Quillstack package, bringing an existing one up to standard, reviewing one before release, or adding one to quillstack.org.
---

# A Quillstack package

Every package in this framework looks the same from the outside. A reader who has read one
README knows where to look in the next; a maintainer who has released one knows how to release
the next. This is that shape.

Work through it in order. Each section says what to produce and how to check it.

**Most of this is not about PHP.** Quillstack is going to exist in more than one language, and a
package written in any of them takes the same shape: the same README, the same badges, the same
repository metadata, the same release discipline. Where a rule belongs to one ecosystem it says
so. The machine-readable form of all of it is `standard/rules.json` in `quillstack/standards`,
which every language's checker reads — one file, so a rule cannot disagree with itself.

**Do not check any of this by hand.** Run it:

```shell
composer require --dev quillstack/standards
./vendor/bin/standards check --online
```

The offline checks read the files. `--online` also asks the services: whether every badge
actually renders, and whether SonarCloud is configured so the quality gate is computed at all.
Both of those have been missed by hand repeatedly, which is why they are in a tool now.

## Before anything: the rule the whole thing rests on

**Every example is run before it is written down.** Not read, not reasoned about — executed, and
its actual output pasted in. This has found real bugs in this project more often than any other
practice: PSR-7 violations, a stream with no position, a benchmark measuring nothing, a `.env`
parser returning `'5432 # the default'`.

Write a scratch script that runs every snippet the README will contain, run it, and build the
README from what it printed. If a snippet cannot be run, it does not go in.

## 1. composer.json

```json
{
    "name": "quillstack/<name>",
    "description": "<one sentence, no trailing full stop needed, says what it does>",
    "type": "library",
    "license": "MIT",
    "keywords": ["<topic>", "<topic>", "quillstack", "php8"],
    "homepage": "https://quillstack.org/packages/<name>",
    "authors": [{"name": "Radek Ziemniewicz", "email": "radek@quillstack.org"}],
    "require": {"php": "^8.1"},
    "require-dev": {
        "phpstan/phpstan": "^2.0",
        "quillstack/unit-tests": "^0.9"
    },
    "autoload": {
        "psr-4": {
            "Quillstack\\<Namespace>\\": "src/",
            "Quillstack\\<Namespace>\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "unit-tests",
        "test:coverage": "phpdbg -qrr ./vendor/bin/unit-tests",
        "stan": "phpstan analyse"
    },
    "extra": {"branch-alias": {"dev-main": "<line>.x-dev"}}
}
```

- `branch-alias` names the line the tags are on, and **moves when the line does**. Fifteen
  packages once carried `0.6.x-dev` while their tags had reached `0.13.0`: Composer reads the
  alias to decide what `dev-main` is, so a stale one puts the development branch in a range
  nobody asked for, and does it silently. Bumping a minor means bumping this in the same commit.
- `homepage` points at the **package's own documentation page**, never the site root.
- Dependencies on other Quillstack packages use a minor constraint with a floor: `^0.7.1`.
  Raising a floor is not a breaking change and does not cascade.

## 2. Files every package has

```
.github/workflows/tests.yml    copy from an existing package, unchanged
.github/dependabot.yml         github-actions weekly, plus npm where there is any
.gitignore                     ends with a newline
.styleci.yml                   risky: false, preset psr12
phpstan.neon
sonar-project.properties       sonar.projectKey=quillstack_<name>
LICENSE
README.md
composer.json
src/
tests/
```

Actions in workflows are **pinned to full commit SHAs** with the version in a trailing comment:

```yaml
- uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1
```

A tag is a pointer its owner can move. Dependabot proposes updates; the comment is what it reads
to know the current version, so it must be true.

## 3. README structure

In this order. Do not add sections between them; do add sub-sections inside them.

1. **`# Quillstack <Name>`**
2. **Badges** — the full block, see below
3. **One or two sentences** saying what it does, in plain words
4. **Why it exists** — the argument. What goes wrong without it, or what everybody else does and
   why this does something different. This is the section people actually read; it is not
   marketing, it is the reasoning. Where there is a competitor, name it and say what it does.
5. **Requirements** — PHP version, extensions
6. **Installation** — `composer require quillstack/<name>`
7. **Usage** — the simplest thing that works, first. Real output as a comment or a fenced block.
8. **The interesting parts** — one sub-section per idea, each with a runnable example
9. **Benchmark**, where there is anything to compare against — see below
10. **Tests** — `composer test`, `composer stan`
11. **The rest of Quillstack** — one line plus links to 3–5 related packages
12. **License** — MIT, linking the LICENSE file on GitHub

A starter skeleton — anything whose manifest says `type: project` — merges 6 and 7 into a single
**Getting started**. There is nothing to add to a project which already is the project, and
splitting the two there makes the README worse to read rather than more uniform. This is the one
place the section list bends, and only for that type.

Cross-links between packages use their GitHub URLs in the README; the documentation site
rewrites them to `/packages/<name>` at build time.

## 4. Badges

Exactly this block, in this order:

```markdown
[![Tests](https://github.com/quillstack/<name>/actions/workflows/tests.yml/badge.svg)](https://github.com/quillstack/<name>/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/quillstack/<name>.svg)](https://packagist.org/packages/quillstack/<name>)
[![Downloads](https://img.shields.io/packagist/dt/quillstack/<name>.svg)](https://packagist.org/packages/quillstack/<name>)
[![PHP Version](https://img.shields.io/packagist/php-v/quillstack/<name>)](https://packagist.org/packages/quillstack/<name>)
[![StyleCI](https://github.styleci.io/repos/<repo-id>/shield?branch=main)](https://github.styleci.io/repos/<repo-id>?branch=main)
[![CodeFactor](https://www.codefactor.io/repository/github/quillstack/<name>/badge)](https://www.codefactor.io/repository/github/quillstack/<name>)
[![Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=quillstack_<name>&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=quillstack_<name>)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=quillstack_<name>&metric=coverage)](https://sonarcloud.io/summary/new_code?id=quillstack_<name>)
[![Maintainability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_<name>&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_<name>)
[![Reliability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_<name>&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_<name>)
[![Security](https://sonarcloud.io/api/project_badges/measure?project=quillstack_<name>&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_<name>)
[![License](https://img.shields.io/packagist/l/quillstack/<name>)](https://github.com/quillstack/<name>/blob/main/LICENSE)
```

The StyleCI repo id **is the GitHub repository's numeric id**:

```shell
gh api repos/quillstack/<name> --jq '.id'
```

Check each badge actually renders before calling it done — a 404 badge is worse than no badge:

```shell
curl -s -o /dev/null -w "%{http_code}\n" -L "<badge url>"
```

## 5. Benchmark sections

Where other people solve the same problem, measure against them. A benchmark without these is
not evidence:

- **Name the exact versions of everything compared**, including PHP. Read them from
  `composer.lock`, never from memory:
  ```shell
  composer show --locked --format=json | python3 -c "import json,sys; [print(f\"{p['name']} {p['version']}\") for p in json.load(sys.stdin)['locked']]"
  ```
- **Use `quillstack/benchmark`**, not a hand-rolled loop.
- **Interleave the runs** — measure A, B, C, A, B, C rather than all of A then all of B, so a
  busy machine does not become a result. Report a median of at least three.
- **Say what is being measured.** `benchmark:console`'s `Took` and `calls per second` include
  process start-up, which is identical for everybody; the column that means anything is
  `avg call time`, which is what the measured script reports about itself.
- **Say what the other libraries do that this one does not.** Being faster because you do less
  is not being faster. If the comparison is not like-for-like, the table says so, above the
  numbers rather than below them.

## 6. GitHub repository metadata

```shell
gh repo edit quillstack/<name> \
    --description "<the same sentence as composer.json description>" \
    --homepage "https://quillstack.org/packages/<name>" \
    --add-topic php --add-topic php8 --add-topic quillstack \
    --add-topic <what it is> --add-topic <what it implements>
```

Five to ten topics: the language, the standard it implements where there is one, the problem it
solves, and `quillstack`.

## 7. The documentation page

Pages are **generated from the README** by `quillstack.org`; none is written by hand. To add a
package, add one line to `scripts/packages.js` in `quillguild/quillstack.org`:

```js
['<name>', '<Title in the sidebar>', '<one line, what it is for>'],
```

The page, the sidebar entry and the row in the index all follow from it. Then:

```shell
gh api -X POST repos/quillguild/quillstack.org/dispatches -f event_type=package-updated
```

A build also runs daily, so a README change reaches the site without anybody touching it.

**To change what a page says, change the README.** There is no second copy.

## 8. Releasing

Version rules, in `0.x`, are Composer's: the **minor** is the breaking position. `0.6.1` is a
fix; `0.7.0` may break you.

The order matters, and it is two commands, not one:

```shell
# 1. verify, and READ THE OUTPUT
./vendor/bin/unit-tests
vendor/bin/phpstan analyse

# 2. only then
git push origin main
git tag -a v0.7.0 -m "Version 0.7.0"
git push origin v0.7.0
```

Combining verification and tagging into one command line is how four releases in this project
went out broken. Run them separately and read the first before typing the second.

**A published tag is never moved.** Packagist caches the first archive it reads for a tag, so
moving one leaves the registry serving old code under a new number and nobody can tell which
they have. Fix a bad release with another release.

After tagging, check CI is green on the tag and that Packagist has the version:

```shell
curl -s "https://repo.packagist.org/p2/quillstack/<name>.json?$(date +%s)" | grep -o '"version":"[^"]*"' | head -3
```

Cache-bust that URL; Packagist's metadata CDN holds for about 15 minutes and will happily tell
you the version does not exist.

**Tagging is not publishing.** Packagist hears about a tag through a webhook, and a webhook can
fail: one on this project answered `500`, so a release looked done — green CI, tag on GitHub —
and nobody could install it. It went unnoticed long enough for the next release to be missing
too. `standards check --online` checks this now; where it fails, look at the repository's
webhook deliveries and redeliver the one that failed:

```shell
gh api repos/quillstack/<name>/hooks --jq '.[] | select(.config.url | contains("packagist")) | .id'
gh api "repos/quillstack/<name>/hooks/<id>/deliveries" --jq '.[] | "\(.delivered_at) \(.status_code)"'
gh api -X POST "repos/quillstack/<name>/hooks/<id>/deliveries/<delivery>/attempts"
```

## 9. Verifying a release properly

Green tests are not proof the package works — they run against the working tree. Install the
published package into an empty project and use it:

```shell
mkdir /tmp/verify && cd /tmp/verify
composer require quillstack/<name>:^0.7
# then actually call it
```

A symlinked vendor directory hides constraint bugs, which is how a too-loose `^0.6` once shipped
a release that could not work.

## 10. The checklist

Run `./vendor/bin/standards check --online` first — it covers most of this. What is left is
what no tool can see:

- [ ] every README example was executed and its real output pasted in
- [ ] `composer test` green, output read
- [ ] `phpstan analyse` clean, output read
- [ ] `phpdbg -qrr vendor/bin/unit-tests` — coverage known
- [ ] badges all render (checked with curl, not by eye)
- [ ] `composer.json` homepage → `/packages/<name>`
- [ ] GitHub description, homepage and topics set
- [ ] workflow actions pinned to SHAs with true version comments
- [ ] entry added to `scripts/packages.js` on the site
- [ ] tagged after verification, not with it
- [ ] Packagist has the tag
- [ ] the documentation page is live and says what the README says
