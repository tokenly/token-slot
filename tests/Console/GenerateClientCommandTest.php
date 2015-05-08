<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class GenerateClientCommandTest extends TestCase {

    // yes, this test requires a database to be set up
    protected $use_database = true;

    /**
     * Generates a client using the console command and validates the attributes
     */
    public function testGenerateClientSuccess() {
        $kernel = app('Illuminate\Contracts\Console\Kernel');

        // call the command like we were calling it from the command line
        //   and get the output
        $kernel->call('generateClient', ['email' => 'testone@tokenly.co', 'address' => '1JztLWos5K7LsqW5E78EASgiVBaCe6f7cD']);
        $output = $kernel->output();

        // make sure it was successful
        PHPUnit::assertContains("Client [testone@tokenly.co] created", $output);

        // check that the user in the database table
        $user = User::where('email', '=', 'testone@tokenly.co')->first();
        PHPUnit::assertNotEmpty($user);
        PHPUnit::assertEquals('testone@tokenly.co', $user['email']);
        PHPUnit::assertEquals('1JztLWos5K7LsqW5E78EASgiVBaCe6f7cD', $user['forward_address']);
        PHPUnit::assertTrue($user['activated']);
        PHPUnit::assertEquals(64, strlen($user['api_key']));
    }

    /**
     * Test an invalid email address
     * @expectedException        Exception
     * @expectedExceptionMessage Invalid email address
     */
    public function testGenerateClientBadEmail() {
        app('Illuminate\Contracts\Console\Kernel')->call('generateClient', ['email' => 'abademail', 'address' => '1JztLWos5K7LsqW5E78EASgiVBaCe6f7cD']);
    }

    /**
     * Test an invalid BTC address
     * @expectedException        Exception
     * @expectedExceptionMessage Invalid BTC address
     */
    public function testGenerateClientBadAddress() {
        app('Illuminate\Contracts\Console\Kernel')->call('generateClient', ['email' => 'testone@tokenly.co', 'address' => 'abadaddress']);
    }

    /**
     * Test a duplicate email throws an exception
     * @expectedException        Exception
     * @expectedExceptionMessage Client already exists
     */
    public function testGenerateClientDuplicateEmail() {
        app('Illuminate\Contracts\Console\Kernel')->call('generateClient', ['email' => 'duplicateguy@tokenly.co', 'address' => '1JztLWos5K7LsqW5E78EASgiVBaCe6f7cD']);
        app('Illuminate\Contracts\Console\Kernel')->call('generateClient', ['email' => 'duplicateguy@tokenly.co', 'address' => '1JztLWos5K7LsqW5E78EASgiVBaCe6f7cD']);
    }

}
