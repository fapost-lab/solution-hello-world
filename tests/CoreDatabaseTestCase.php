<?php

declare(strict_types=1);

namespace Fapost\HelloWorld\Tests;

use App\Domains\Solution\Services\ActiveSolutions;
use App\Domains\Solution\Services\PublishedSolutionUi;
use App\Http\Shell\SolutionExtensions;
use App\Http\Shell\SolutionUi;
use App\Http\Shell\SolutionUiMapInterface;
use Illuminate\Support\Facades\DB;

/**
 * A test that needs Core's tables: the tenant tables on the default connection (SQLite in memory, so no schemas) and
 * the landlord tables with the default tenant `main`, migrated fresh for every test as the application itself is.
 *
 * Gap: Foundation has no testing kit for Solutions yet, so these tests lean on Core internals a package has no
 * contract for: Core's migration paths and the `tenants` columns here; {@see forgetSolutionState()} resetting Core's
 * own singletons (`App\Domains\Solution\…`, `App\Http\Shell\…`) to simulate a new request; and the `App\…`
 * imports of the feature tests (action registry, publisher, installed catalog, call tester, tenant switcher, user
 * model, seeder). `src/` never touches any of it.
 */
abstract class CoreDatabaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--path' => 'database/migrations/tenant', '--force' => true])->assertSuccessful();
        $this->artisan('migrate', ['--path' => 'database/settings', '--force' => true])->assertSuccessful();
        $this->artisan('migrate', ['--database' => 'landlord', '--force' => true])->assertSuccessful();

        DB::connection('landlord')->table('tenants')->insert([
            'id'          => '00000000-0000-0000-0000-000000000001',
            'slug'        => 'main',
            'schema_name' => 'main',
            'status'      => 'active',
            'config'      => '{}',
        ]);
    }

    /**
     * What a request or the process remembered about Solutions; a new request would start without it.
     */
    protected function forgetSolutionState(): void
    {
        foreach ([ActiveSolutions::class, PublishedSolutionUi::class, SolutionUi::class, SolutionUiMapInterface::class, SolutionExtensions::class] as $abstract) {
            $this->app->forgetInstance($abstract);
        }
    }
}
