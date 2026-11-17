<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\PageVisitor;
use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PageVisitorTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'fsr_quiz',
        ]);
        DB::purge('mysql');
        Cache::flush();
        PageVisitor::query()->delete();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        PageVisitor::query()->delete();
        parent::tearDown();
    }

    public function test_page_visitor_is_logged_when_user_visits_front_page(): void
    {
        User::where('mobile', '9988776655')->forceDelete();

        $user = User::create([
            'tenant_id' => 1,
            'name' => 'Visitor Test User',
            'mobile' => '9988776655',
            'password' => bcrypt('password'),
            'is_active' => 1,
        ]);

        $response = $this->actingAs($user, 'web')
            ->get('/fsrapp/home', [
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
            ]);

        $response->assertStatus(200);

        // Verify record in database
        $visitor = PageVisitor::where('user_id', $user->id)->first();
        $this->assertNotNull($visitor, 'Visitor record should be created');
        $this->assertEquals('Home Page', $visitor->page_name);
        $this->assertEquals('Mobile', $visitor->device_type);
        $this->assertEquals('Safari', $visitor->browser);
        $this->assertEquals('iOS', $visitor->platform);
        $this->assertEquals(now()->toDateString(), $visitor->visit_date->toDateString());

        $user->forceDelete();
    }

    public function test_visitor_tracking_is_strictly_paused_when_quiz_is_playing_to_protect_server_load(): void
    {
        // 1. Create active playing quiz
        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'Live Gameplay Quiz',
            'visibility' => 'public',
            'approve_status' => 'approved',
            'complete_status' => 'pending',
            'scheduled_at' => now()->subMinutes(2), // 2 mins ago (currently live)
        ]);

        Cache::forget('is_any_quiz_playing_now');
        $this->assertTrue(Quiz::isAnyQuizPlayingNow());

        // 2. Visit front page during active quiz
        $response = $this->get('/fsrapp/login');
        $response->assertStatus(200);

        // 3. Assert NO visitor record was created in database to ensure zero CPU/MySQL load
        $count = PageVisitor::count();
        $this->assertEquals(0, $count, 'Visitor tracking MUST be completely paused when a quiz is playing');

        // Cleanup
        $quiz->forceDelete();
        Cache::forget('is_any_quiz_playing_now');
    }

    public function test_debounce_prevents_duplicate_records_on_rapid_reloads(): void
    {
        // First visit
        $this->get('/fsrapp/login');
        $this->assertEquals(1, PageVisitor::count());

        // Immediate reload of the exact same page from the same client IP
        $this->get('/fsrapp/login');
        $this->assertEquals(1, PageVisitor::count(), 'Debounce should prevent spamming visitor records on rapid reload');
    }

    public function test_ajax_and_api_requests_are_not_logged(): void
    {
        $this->getJson('/fsrapp/quizes/api/sounds');
        $this->assertEquals(0, PageVisitor::count(), 'API/JSON polling endpoints must not be logged as page visits');
    }

    public function test_superadmin_can_view_page_visitors_dashboard_with_metrics_and_filters(): void
    {
        // Seed 3 visitor records
        PageVisitor::create([
            'tenant_id' => 1,
            'page_name' => 'Home Page',
            'page_url' => 'http://localhost/fsrapp/home',
            'ip_address' => '127.0.0.1',
            'device_type' => 'Mobile',
            'browser' => 'Chrome',
            'platform' => 'Android',
            'visit_date' => now()->toDateString(),
        ]);

        PageVisitor::create([
            'tenant_id' => 1,
            'page_name' => 'All Quiz List',
            'page_url' => 'http://localhost/fsrapp/allquizlist',
            'ip_address' => '127.0.0.2',
            'device_type' => 'Desktop',
            'browser' => 'Firefox',
            'platform' => 'Windows',
            'visit_date' => now()->toDateString(),
        ]);

        $admin = AdminUser::first() ?: AdminUser::create([
            'tenant_id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@fsrquiz.test',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->actingAs($admin, 'superadmin')
            ->get(route('superadmin.visitors.index'));

        $response->assertStatus(200);
        $response->assertSee('Front Page Visitors');
        $response->assertSee('Home Page');
        $response->assertSee('All Quiz List');
        $response->assertSee('127.0.0.1');
        $response->assertSee('127.0.0.2');
    }
}
