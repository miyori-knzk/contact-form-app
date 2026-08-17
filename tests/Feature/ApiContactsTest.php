<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContactsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 一覧で_jso_n形式で取得できる()
    {
        $tagIds = [];

        $category = Category::factory()->create();
        $contacts = Contact::factory()->count(5)->create();
        $tags = Tag::factory()->count(5)->create();

        foreach ($contacts as $contact) {
            $rand = mt_rand(1, 5);
            $tagIds = $tags->random($rand)->pluck('id')->toArray();

            $contact->tags()->attach($tagIds);
        }

        $url = '/api/v1/contacts';

        $response = $this->getJson($url);

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

    }

    /** @test */
    public function 一覧で検索が機能している(): void
    {
        Category::factory()->count(5)->create();
        Contact::factory()->count(5)->create();

        Contact::factory()->create(['email' => 'demo@test',
            'gender' => 1,
            'category_id' => 1,
        ]);

        $urlQuery = http_build_query([
            'email' => 'demo@test',
            'gender' => 1,
            'category_id' => 1,
            'date' => date('Y-m-d'),
        ]);

        $response = $this->getJson('/api/v1/contacts?' . $urlQuery);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    /** @test */
    public function 一覧でページネーションが機能している(): void
    {
        Category::factory()->count(3)->create();
        Contact::factory()->count(100)->create();
        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.total', 100);
        $response->assertJsonPath('meta.last_page', 5);
    }

    /** @test */
    public function 一覧でバリデーションエラー時は422が返る(): void
    {
        Category::factory()->count(3)->create();
        Contact::factory()->count(5)->create();

        $urlQuery = http_build_query([
            'gender' => 99,
        ]);

        $response = $this->getJson('/api/v1/contacts?' . $urlQuery);

        $response->assertStatus(422);
    }

    /** @test */
    public function jso_n形式の詳細が取得できる()
    {
        $tagIds = [];

        $category = Category::factory()->create();
        $contact = Contact::factory()->create();
        $tags = Tag::factory()->count(5)->create();

        $rand = mt_rand(1, 5);
        $tagIds = $tags->random($rand)->pluck('id')->toArray();

        $contact->tags()->attach($tagIds);

        $url = '/api/v1/contacts/1';

        $response = $this->getJson($url);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'category' => [],
                'last_name',
                'first_name',
                'gender',
                'email',
                'tel',
                'address',
                'building',
                'detail',
                'tags' => [],
                'created_at',
                'updated_at',
            ],
        ]);
    }
}
