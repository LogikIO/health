<?php

namespace PragmaRX\Health\tests\phpunit\Service;

use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use PragmaRX\Health\ServiceProvider;

class ServiceTest extends PHPUnit_Framework_TestCase
{
    private $service;

    private function getConfig()
    {
        $config = require __DIR__.'/config.php';

        return $config;
    }

    public function setUp()
    {
        $this->app = require __DIR__.'/../../../vendor/laravel/laravel/bootstrap/app.php';

        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

        $kernel->handle(
            $request = \Illuminate\Http\Request::capture()
        );

        $this->config = $this->app->make('config');

        $this->config->set('health', $this->getConfig());

        $this->serviceProvider = $this->app->register(ServiceProvider::class);

        $this->service = $this->serviceProvider->getHealthService();

        $this->resources = $this->service->checkResources();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Collection::class, $this->resources);
    }

    public function testConfigWasLoadedProperly()
    {
        $this->assertEquals($this->resources['health']['error_message'], 'This is a test only error message.');
    }

    public function testResourcesHasTheCorrectCount()
    {
        $this->assertCount(9, $this->resources->toArray());
    }

    public function testResourcesItemsMatchConfig()
    {
        $this->assertEquals(
            [
                'health',
                'cache',
                'cloud_storage',
                'database',
                'filesystem',
                'framework',
                'http',
                'https',
                'mail',
            ],
            $this->resources->keys()->toArray()
        );
    }

    public function testResourcesWhereChecked()
    {
        $healthCount = $this->resources->reduce(function ($carry, $item) {
            return $carry + (isset($item['health']['healthy'])
                    ? 1
                    : 0);
        }, 0);

        $this->assertEquals(9, $healthCount);
    }
}
