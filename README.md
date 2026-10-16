# fapost/solution-hello-world

The smallest working [FaPost](https://docs.fapost.in) Solution. It is a demo for contributors and a
test subject for Core's Solution mechanism on a genuinely Composer-installed package.

Docs: [Solutions](https://docs.fapost.in/extending/solutions) and
[Console UI](https://docs.fapost.in/extending/console-ui).

## What it shows

| Piece | Where |
|---|---|
| The manifest: `type: fapost-solution` and `extra.fapost` (id `hello`, provider, actions, UI bundle) | `composer.json` |
| An action for the flow `call` node: `hello.greet` takes a `name` and returns `{"greeting": "Hello, <name>!"}` (`world` when the name is missing or blank) | `src/Actions/GreetAction.php` |
| A provider that registers the action, an ability, one console application with one menu entry, and one route | `src/HelloWorldServiceProvider.php` |
| One console page, `hello::Home`, written as a Vue single-file component | `resources/js/pages/Home.vue`, `src/HelloWorldController.php` |
| The prebuilt bundle Core publishes and the browser loads, with the declaration `fapost-ui.json` | `dist/` |

`src/` never imports `App\…`: it depends on `fapost/foundation` only. The integration tests in `tests/` do, because Foundation has no testing kit for Solutions yet.

## Install into a Core checkout

The package is added on top of Core with a [composer overlay](https://docs.fapost.in/extending/development-setup),
so Core's own `composer.json` stays untouched. From the root of a Core checkout:

```bash
git clone https://github.com/fapost-lab/solution-hello-world.git packages/solution-hello-world
```

Add these entries to your `composer.overlay.json` (create the file if there is none; keep what is already in it):

```json
{
  "repositories": [{ "type": "path", "url": "packages/solution-hello-world" }],
  "require": { "fapost/solution-hello-world": "@dev" }
}
```

```bash
composer overlay
```

Further Composer commands in that checkout need `COMPOSER=composer.local.json`.

Then let Core validate the manifest and publish the bundle, and switch the Solution on for a tenant:

```bash
php artisan platform:update
php artisan solutions:list --tenant=<slug>
php artisan solutions:activate hello --tenant=<slug>
```

`platform:update --validate-only` only checks the manifests. Once activated, the tenant's console shows a
"Hello World" application whose page greets the world. `php artisan solutions:deactivate hello --tenant=<slug>`
switches it off again.

## Use the action in a flow

In a flow, add a `call` node with the `handler` transport and `hello.greet` as its target:

```json
{
  "id": "greet",
  "type": "call",
  "version": 1,
  "config": {
    "transport": "handler",
    "target": "hello.greet",
    "parameters": { "name": "Ada" },
    "result_mapping": [
      { "from": "payload.greeting", "to": { "name": "greeting", "storage": "session" } }
    ]
  }
}
```

The call's payload is `{"greeting": "Hello, Ada!"}`; the mapping above stores it in the session variable `greeting`. While the Solution is not active for the
tenant, the node fails with `solution_inactive: hello`.

The page title comes from the server (`hello::console.app`). Only `en` translations are shipped; other locales fall back to them.

## Develop the UI

Core has no Node, so the built `dist/` is committed. After changing `resources/js/`:

```bash
npm ci
npm run build
```

`npm run build` runs Vite in library mode (`vite.config.js`), keeping `vue`, `@inertiajs/vue3` and `@fapost/ui`
external because the console supplies them, and then writes `dist/fapost-ui.json` (`scripts/write-manifest.mjs`).
All three are pinned in `package.json` at the versions Core ships, so imports resolve against the real kit; the
`kit`, `vue` and `inertia` ranges in `fapost-ui.json` come from those installed versions, and Core refuses to
activate the Solution when its own versions fall outside them. Commit the result; CI rebuilds it and fails if it differs from the committed files.

> This hand-rolled Vite configuration and manifest script are temporary. The Vite preset
> `@fapost/vite-plugin-solution` will replace them.

## Run the tests

The tests boot Core's real application with this package installed, so run them inside a Core checkout
that has the package installed as above:

```bash
php artisan config:clear
cd packages/solution-hello-world
php ../../vendor/bin/phpunit -c phpunit.xml
```

Code style: `../../vendor/bin/pint --config pint.json src tests lang`.

## Contributing

Contributions are licensed under Apache-2.0 and need the Contributor Licence Agreement, which a bot asks you to
sign on your first pull request. CI checks out the public `fapost-lab/core` and runs the tests
inside it, in pull requests from forks too; nothing needs to be configured.

## License

Apache-2.0, see [LICENSE](LICENSE).
