// Writes dist/fapost-ui.json next to the files `vite build` has just emitted.
// Temporary, like vite.config.js: the Vite preset will do this itself.
import { readdirSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const dist = join(dirname(fileURLToPath(import.meta.url)), '..', 'dist')
const assets = readdirSync(join(dist, 'assets')).sort()

const only = (pattern) => {
    const found = assets.filter((file) => pattern.test(file))

    if (found.length !== 1) {
        throw new Error(`Expected exactly one file matching ${pattern} in dist/assets, found ${found.length}.`)
    }

    return `assets/${found[0]}`
}

const manifest = {
    schema: 1,
    solution: 'hello',
    kit: '^0.1',
    vue: '^3.5',
    inertia: '^3.0',
    pages: { Home: only(/^home-.+\.js$/) },
}

const css = assets.filter((file) => file.endsWith('.css')).map((file) => `assets/${file}`)

if (css.length > 0) {
    manifest.css = css
}

writeFileSync(join(dist, 'fapost-ui.json'), `${JSON.stringify(manifest, null, 4)}\n`)
