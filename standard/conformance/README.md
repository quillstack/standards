# Conformance cases

Example packages, and what a checker must say about each.

There is one set of rules — `../rules.json` — and there will be a checker for every language
Quillstack exists in, because asking a Python developer to install PHP to check a Python package
would make Python the guest. Two implementations of the same rules drift: not in what the rules
say, which is one file, but in how each reads them. One checker decides a section is missing
where the other decides it is at the wrong level, and nobody notices until the two disagree
about somebody's package.

These cases are what stops that. Each directory is a small package; each `expected.json` says
which checks must fail on it and why. Every checker runs all of them and must agree.

## The shape of a case

```
<case>/
    expected.json      what a checker must find
    composer.json      or pyproject.toml, or whatever the ecosystem uses
    README.md
    …
```

`expected.json` lists, per check, the statuses a conforming checker produces:

```json
{
    "about": "one sentence saying what this case is for",
    "scope": "universal",
    "checks": {
        "readme sections": {"failures": 2},
        "badges": {"failures": 0}
    }
}
```

`failures` is a count rather than a list of messages on purpose. The wording of a finding is a
checker's own business and will differ between languages; **what** it objects to is the rule,
and that is what has to agree.

`scope` says which checkers a case applies to. A case about `composer.json` scripts is `php`; a
case about README sections is `universal` and every checker runs it.
