<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function キーワードでお問い合わせの検索ができる(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->create();
        Contact::factory()->count(5)->create();
        Contact::factory()->create(['email' => 'test@test']);

        $response = $this->actingAs($user)->get('/admin?keyword=test');

        $response->assertStatus(200);
        $response->assertSee('test');
        $response->assertDontSee('example');
    }

    /** @test */
    public function 性別でお問い合わせの検索ができる(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->create();

        $man = Contact::factory()->create([
            'first_name' => '太郎',
            'gender' => 1,
        ]);
        $woman = Contact::factory()->create([
            'first_name' => '花子',
            'gender' => 2,
        ]);
        $other = Contact::factory()->create([
            'first_name' => '零',
            'gender' => 3,
        ]);

        $response = $this->actingAs($user)->get('/admin?gender=1');

        $response->assertStatus(200);
        $response->assertSee($man->first_name);
        $response->assertDontSee($woman->first_name);
        $response->assertDontSee($other->first_name);
    }

    /** @test */
    public function 日付でお問い合わせの検索ができる(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->create();

        Contact::factory()->count(5)->create();
        Contact::factory()->create([
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($user)->get('/admin?date=2026-08-11');

        $response->assertStatus(200);
    }

    /** @test */
    public function 検索結果が７件ごとに表示される(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->create();

        Contact::factory()->count(21)->create();

        $p1 = $this->actingAs($user)->get('/admin?page=1');
        $p1->assertStatus(200);
        $p1->assertSee('next');
        $p1->assertDontSee('prev');

        $p3 = $this->actingAs($user)->get('/admin?page=3');
        $p3->assertStatus(200);
        $p3->assertSee('prev');
        $p3->assertDontSee('next');
    }

    /** @test */
    public function お問い合わせ詳細ページを表示できる(): void
    {
        $user = User::factory()->create();
        Category::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.show', $contact));

        $response->assertStatus(200);
        $response->assertViewHas('contact');
    }

    /** @test */
    public function お問い合わせを削除できる(): void
    {
        $user = User::factory()->create();
        Category::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->delete(route('admin.destroy', $contact));

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }
}
