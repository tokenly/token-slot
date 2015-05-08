<?php

class TestCase extends Illuminate\Foundation\Testing\TestCase {

	/**
	 * Use if this test interacts with the database
	 * @var boolean
	 */
	protected $use_database = false;

	/**
	 * Creates the application.
	 *
	 * @return \Illuminate\Foundation\Application
	 */
	public function createApplication()
	{
		$app = require __DIR__.'/../bootstrap/app.php';

		$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

		return $app;
	}

    public function setUp()
    {
        parent::setUp();

        if ($this->use_database) { $this->setUpDb(); }
    }


    protected function setUpDb()
    {
        app('Illuminate\Contracts\Console\Kernel')->call('migrate');
    }

    protected function teardownDb()
    {
        app('Illuminate\Contracts\Console\Kernel')->call('migrate:reset');
    }

}
