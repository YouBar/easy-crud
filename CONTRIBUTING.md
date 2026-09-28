# Contributing

## Getting set up

```bash
git clone git@github.com:Youbar/easy-crud.git
cd easy-crud
composer install
composer test
```

Requires PHP 8.2+. There is no Node toolchain in this repository: `docs/content`
is plain Markdown, rendered by youbar.nl.

## The checks

```bash
composer test      # PHPUnit
composer lint      # Pint, Laravel preset
composer analyse   # PHPStan level 6, no baseline
```

All three run in CI and all three must pass. The PHPStan baseline is
deliberately absent — if a change cannot pass level 6, fix the types rather
than record the failure.

## Commit convention

[Conventional Commits](https://www.conventionalcommits.org/), the same
`@commitlint/config-conventional` rules used across youbar projects.

```
feat: add cursor pagination strategy
fix: resolve requests for the destroy action
docs: explain the precedence ladder
```

| Prefix | Effect on the next release |
|---|---|
| `feat:` | minor bump |
| `fix:` `perf:` `refactor:` | patch bump |
| `feat!:` or `BREAKING CHANGE:` footer | major bump |
| `docs:` `test:` `ci:` `build:` `chore:` `style:` | no release |

**The pull request title is what matters.** This repository squash-merges, so
the PR title becomes the commit on `main`, and that is what release automation
reads. CI lints the title, not your individual commits — work however you like
on the branch.

## Releasing

Releases are automated and nobody tags by hand.

1. Merge conventional commits into `main`.
2. [release-please](https://github.com/googleapis/release-please) keeps a
   rolling `chore: release` pull request containing the version bump and the
   generated changelog.
3. Merging that PR tags the release and publishes it on GitHub.
4. Packagist ingests the tag through its GitHub App — there is no publish
   credential in CI, by design.
5. `docs.yml` tells youbar.nl a new tag exists, so the documentation pointer
   moves with the release.

To cut `1.0.0` out of the `0.x` line, put a footer on the commit:

```
feat: whatever the change is

Release-As: 1.0.0
```

## Writing documentation

Pages live in `docs/content` as Markdown and are rendered by youbar.nl. There
is nothing to build locally.

```bash
node .github/scripts/check-docs.mjs
```

That checks front matter and internal links, and runs on every PR. Conventions:

- Numeric prefixes (`2.core-concepts/`, `1.introduction.md`) set the order and
  are stripped from the URL.
- Every page needs `title` and `description`.
- Internal links omit the site prefix: `/core-concepts/precedence`.
- **Every page describing a default must say how to override it, inline, with
  code** — not by linking elsewhere.
- **The quickstart never mentions contracts, roles or precedence.** If the
  zero-configuration case is not two lines there, the defaults are wrong.

## Design rules worth knowing before proposing a change

- **Level 1 stays two lines.** A controller declaring only `$model` must keep
  working with no other files in the project.
- **Resolution and behaviour stay separate.** The conventions table answers
  "which class". Only four roles have behaviour attached, and adding a fifth is
  a deliberate decision, not a config option.
- **No parity regressions.** `ParityTest` reproduces every shape from the
  thirteen controllers this package was extracted from, and asserts by
  reflection that none of them needs to override a CRUD action. If a change
  forces one of them to, the change is wrong.
- **One precedence ladder.** Property, method, config, fallback — the same for
  every role. Per-role special cases are how this becomes unlearnable.
