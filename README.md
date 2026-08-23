# Quillstack Standards

Checks a package against the shape every Quillstack package takes: the README, the badges, the
manifest, the workflow, the quality gate. The rules are language-neutral data; this is the
reader for them.

## Why this exists

Every package in this framework looks the same from the outside, and until now that was held up
by somebody remembering. It did not hold.

Across one working week the same few things went wrong by hand, repeatedly: **a SonarCloud
project created with `master` as its main branch**, so every analysis landed on a short branch
beside the real one and the badge reported a branch nobody had analysed — missed five times, each
time noticed only because a badge looked wrong. **A new code period never set**, so the quality
gate was not computed at all. Manifests pointing at pages that had moved, three of them at a
different site entirely. A README section one heading too deep. A workflow whose actions were
still pinned to tags.

None of that is hard. All of it is invisible until somebody looks, and looking is exactly what a
person stops doing on the thirtieth package.

**This is also why the rules are a JSON file with no PHP in it.** Quillstack is going to exist in
more than one language, and the shape a package takes — its README, its badges, its repository,
its release — has nothing to do with the language it is written in. A rule kept in two places is
a rule that disagrees with itself within a week; this project has spent enough time proving that.
Each language gets a reader for `standard/rules.json`. The file is the standard.

## Requirements

- PHP 8.1 or newer

## Installation

```shell
composer require --dev quillstack/standards
```

## Usage

```shell
./vendor/bin/standards check
```

```text
Checking quillstack/dotenv-expand against the Quillstack standard

  ok   readme sections 11 sections, in the order the standard sets
  ok   badges          12 badges
  ok   manifest        homepage, description, scripts, alias and files all present
  ok   pinned actions  10 actions pinned to commits
  ok   rendering       nothing that reads correctly and renders wrongly
  ok   quality gate    project key `quillstack_dotenv-expand`, unchecked against SonarCloud

  6 passed, 0 to look at, 0 failed
```

It exits non-zero where anything failed, so CI can use it.

### Asking the services too

Everything above is answered from the files on disk, so it is fast and works without a network.
The questions only a service can answer are behind a flag:

```shell
./vendor/bin/standards check --online
```

```text
  ok   badges          12 badges, all answering
  ok   quality gate    `quillstack_dotenv-expand` on `main`, gate ok
```

**A badge that 404s is worse than no badge** — it reads as a broken project rather than an
unconfigured service — so each one is fetched rather than eyeballed.

### Checking somewhere else

```shell
./vendor/bin/standards check ../dotenv --online
```

## What it checks

| Check | What it is looking for |
| --- | --- |
| readme sections | The sections the standard sets, present and in order. A section one heading too deep is reported as that, not as missing. |
| badges | All twelve, and with `--online` that each one answers. |
| manifest | The homepage is this package's own page, the scripts are there, the branch alias is set, and so are the files every package has. |
| pinned actions | Every action pinned to a commit, with the version in a comment beside it. |
| rendering | Markdown which reads correctly and renders wrongly. |
| quality gate | The Sonar key matches the package, and with `--online` that the main branch is right and the gate is computed at all. |

### The rendering one

A README here is wrapped at a hundred characters, so a sentence breaks wherever it reaches the
margin. Where an inline element lands at the end of a line and a link starts the next, the space
between them is lost when it renders:

```markdown
The second row is this package **with**
[quillstack/dotenv-expand](https://github.com/quillstack/dotenv-expand) on top:
```

comes out as `withquillstack/dotenv-expand`. A plain word before a link is fine, and bold before
plain text is fine — it is only the two together, across the break. Which is the kind of thing
nobody spots while writing and everybody spots while reading.

## The standard itself

`standard/rules.json` is the machine-readable form and `standard/SKILL.md` is the long one, for
a person or an agent doing the work rather than checking it. Both ship with this package, so the
rules and the checker are versioned together and cannot drift apart.

## Tests

```shell
composer test
composer stan
```

## The rest of Quillstack

This is one component of [Quillstack](https://github.com/quillstack), a PHP framework which is
as simple to use as it is strict about what it does.

- [quillstack/cli](https://github.com/quillstack/cli) — the console this runs on
- [quillstack/output](https://github.com/quillstack/output) — what writes the report
- [quillstack/unit-tests](https://github.com/quillstack/unit-tests) — what tests it

## License

MIT — see [LICENSE](https://github.com/quillstack/standards/blob/main/LICENSE).
