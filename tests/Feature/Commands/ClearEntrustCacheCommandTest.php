<?php

namespace Tests\Feature\Commands;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

#[Group('entrust-cache-clear')]
class ClearEntrustCacheCommandTest extends AbstractTestCase
{
    #[Test]
    public function command_executes_successfully()
    {
        $exit = $this->artisan('entrust:cache-clear');
        $this->assertTrue($exit === 0);
    }

    #[Test]
    public function command_clears_permission_role_cache()
    {
        if (!Cache::getStore() instanceof TaggableStore) {
            $this->markTestSkipped('Cache driver does not support tags');
        }

        $tag = Config::get('entrust.permission_role_table');
        Cache::tags($tag)->put('test_key', 'test_value', 60);
        $this->assertEquals('test_value', Cache::tags($tag)->get('test_key'));

        $this->artisan('entrust:cache-clear');
        $this->assertNull(Cache::tags($tag)->get('test_key'));
    }

    #[Test]
    public function command_clears_role_user_cache()
    {
        if (!Cache::getStore() instanceof TaggableStore) {
            $this->markTestSkipped('Cache driver does not support tags');
        }

        $tag = Config::get('entrust.role_user_table');
        Cache::tags($tag)->put('test_key', 'test_value', 60);
        $this->assertEquals('test_value', Cache::tags($tag)->get('test_key'));

        $this->artisan('entrust:cache-clear');
        $this->assertNull(Cache::tags($tag)->get('test_key'));
    }

    #[Test]
    public function command_clears_general_cache()
    {
        Cache::put('test_key_general', 'test_value', 60);
        $this->assertEquals('test_value', Cache::get('test_key_general'));

        $this->artisan('entrust:cache-clear');
        $this->assertNull(Cache::get('test_key_general'));
    }

    #[Test]
    public function command_displays_success_message()
    {
        $this->artisan('entrust:cache-clear')
            ->expectOutputToContain('cleared successfully');
    }

    #[Test]
    public function command_with_verbose_option_shows_details()
    {
        $this->artisan('entrust:cache-clear', ['--verbose' => true])
            ->expectOutputToContain('Cache driver');
    }

    #[Test]
    public function command_is_idempotent_safe_to_run_multiple_times()
    {
        for ($i = 0; $i < 3; $i++) {
            $exit = $this->artisan('entrust:cache-clear');
            $this->assertTrue($exit === 0);
        }
    }

    #[Test]
    public function command_clears_multiple_cache_entries()
    {
        if (!Cache::getStore() instanceof TaggableStore) {
            $this->markTestSkipped('Cache driver does not support tags');
        }

        $tag = Config::get('entrust.permission_role_table');
        Cache::tags($tag)->put('role_1_perms', ['perm1', 'perm2'], 60);
        Cache::tags($tag)->put('role_2_perms', ['perm3', 'perm4'], 60);

        $this->artisan('entrust:cache-clear');

        $this->assertNull(Cache::tags($tag)->get('role_1_perms'));
        $this->assertNull(Cache::tags($tag)->get('role_2_perms'));
    }

    #[Test]
    public function command_returns_success_exit_code()
    {
        $exit = $this->artisan('entrust:cache-clear');
        $this->assertSame(0, $exit);
    }
}
