#!/usr/bin/env node
/**
 * Validates docs/content without building anything.
 *
 * The site that renders these pages lives in another repository, so a broken
 * link here would not surface until that site rebuilt. This replaces the
 * safety net a local prerender would have given us.
 *
 * Checks:
 *   1. every page has title and description front matter
 *   2. every root-relative internal link resolves to a page that exists
 *   3. no link hardcodes the host site's prefix (/docs/easy-crud/...)
 *
 * Usage: node .github/scripts/check-docs.mjs
 */
import { readdir, readFile } from 'node:fs/promises'
import { join, relative, resolve } from 'node:path'

const contentDir = resolve(process.cwd(), 'docs/content')
const SITE_PREFIX = '/docs/easy-crud'

/** Turn a content file path into the route Nuxt Content will give it. */
function routeFor(file) {
  return '/' + relative(contentDir, file)
    .replace(/\.md$/, '')
    .split(/[/\\]/)
    .map(segment => segment.replace(/^\d+\./, ''))
    .join('/')
}

async function walk(dir) {
  const found = []

  for (const entry of await readdir(dir, { withFileTypes: true })) {
    const path = join(dir, entry.name)

    if (entry.isDirectory()) {
      found.push(...await walk(path))
    }
    else if (entry.name.endsWith('.md')) {
      found.push(path)
    }
  }

  return found
}

const files = await walk(contentDir)

if (files.length === 0) {
  console.error('No Markdown found in docs/content.')
  process.exit(1)
}

const routes = new Set(files.map(routeFor))
const problems = []

for (const file of files) {
  const source = await readFile(file, 'utf8')
  const where = relative(process.cwd(), file)

  // 1. front matter
  const frontMatter = source.startsWith('---') ? source.slice(3, source.indexOf('\n---', 3)) : ''

  for (const key of ['title', 'description']) {
    if (!new RegExp(`^${key}:\\s*\\S`, 'm').test(frontMatter)) {
      problems.push(`${where}: missing "${key}" in front matter`)
    }
  }

  // 2 & 3. links, both markdown [](…) and MDC `to: …` props
  const links = [
    ...source.matchAll(/\]\((\/[^)\s#]*)/g),
    ...source.matchAll(/^\s*to:\s*(\/\S+)/gm),
  ].map(match => match[1])

  for (const link of new Set(links)) {
    if (link.startsWith(SITE_PREFIX)) {
      problems.push(`${where}: link "${link}" hardcodes the site prefix — use "${link.slice(SITE_PREFIX.length) || '/'}"`)
      continue
    }

    const target = link.replace(/\/$/, '') || '/'

    if (!routes.has(target)) {
      problems.push(`${where}: link "${link}" points at no page`)
    }
  }
}

console.log(`Checked ${files.length} pages, ${routes.size} routes.`)

if (problems.length > 0) {
  console.error(`\n${problems.length} problem(s):\n`)
  for (const problem of problems) console.error(`  ✗ ${problem}`)
  process.exit(1)
}

console.log('No problems found.')
