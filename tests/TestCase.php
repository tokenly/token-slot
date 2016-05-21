<?php

use Illuminate\Support\Facades\DB;

class TestCase extends Illuminate\Foundation\Testing\TestCase {

    protected $baseUrl = 'http://localhost';

    protected $use_database = false;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function setUp()
    {
        // make sure we are using the testing environment
        parent::setUp();

        if($this->use_database) {
            $this->setUpDb();
        }
    }

    public function tearDown() {
        return parent::tearDown();
    }

    public function setUpDb()
    {
        // migrate the database
        $this->app['Illuminate\Contracts\Console\Kernel']->call('migrate');
    }

    public function teardownDb() {
        $this->app['Illuminate\Contracts\Console\Kernel']->call('migrate:reset');
    }

}
