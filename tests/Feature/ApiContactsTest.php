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

        Contact::factory()->create([
            'email' => 'demo@test',
            'gender' => 1,
            'category_id' => 1,
        ]);

        $urlQuery = http_build_query([
            'keyword' => 'demo@test',
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

    /** @test */
    public function 存在しないコンタクト_i_dで404エラーを返す()
    {
        $response = $this->getJson('/api/v1/contacts/5');

        $response->assertStatus(404);
    }

    /** @test */
    public function ap_iで_pos_t時レコードが作成され201が返る()
    {
        Category::factory()->count(5)->create();
        $tags = Tag::factory()->count(5)->create();

        $contact = Contact::factory()->raw();

        $contact['email'] = 'test@test';
        $contact['first_name'] = 'テスト';

        $rand = mt_rand(1, 5);
        $tagIds['tag_ids'] = $tags->random($rand)->pluck('id')->toArray();

        $response = $this->postJson('/api/v1/contacts', array_merge($contact, $tagIds));

        $response->assertStatus(201);
        $this->assertDatabaseHas('contacts', [
            'first_name' => 'テスト',
            'email' => 'test@test',
        ]);
    }

    /** @test */
    public function ap_iで_pos_t時バリデーションエラーで422が返る()
    {
        Category::factory()->count(5)->create();
        $tags = Tag::factory()->count(5)->create();

        $contact = Contact::factory()->raw();

        $contact['email'] = 'test';
        $contact['first_name'] = str_repeat('あ', 300);

        $rand = mt_rand(1, 5);
        $tagIds['tag_ids'] = $tags->random($rand)->pluck('id')->toArray();

        $response = $this->postJson('/api/v1/contacts', array_merge($contact, $tagIds));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('contacts', [
            'first_name' => str_repeat('あ', 300),
            'email' => 'test',
        ]);
    }

    /** @test */
    public function ap_iで_pu_t時レコードが更新され200が返る()
    {
        Category::factory()->count(5)->create();
        $tags = Tag::factory()->count(5)->create();

        $contact = Contact::factory()->create();

        $contact['email'] = 'test@test';
        $contact['first_name'] = 'テスト';

        $contactArr = $contact->toArray();

        $rand = mt_rand(1, 5);
        $tagIds['tag_ids'] = $tags->random($rand)->pluck('id')->toArray();

        $response = $this->putJson('/api/v1/contacts/' . $contact->id, array_merge($contactArr, $tagIds));

        $response->assertStatus(200);
        $this->assertDatabaseHas('contacts', [
            'first_name' => 'テスト',
            'email' => 'test@test',
        ]);
    }

    /** @test */
    public function ap_iで_pu_t時存在しない_i_dで404が返る()
    {
        Category::factory()->count(5)->create();
        $contactArr = Contact::factory()->raw();
        $response = $this->putJson('/api/v1/contacts/99', $contactArr);

        $response->assertStatus(404);
    }

    /** @test */
    public function ap_iで_pu_t時バリデーションエラーで422が返る()
    {
        Category::factory()->count(5)->create();
        $contact = Contact::factory()->create();

        $contactArr = $contact->toArray();
        $contactArr['gender'] = 99;
        $response = $this->putJson('/api/v1/contacts/' . $contact->id, $contactArr);

        $response->assertStatus(422);
    }

    /** @test */
    public function ap_iで_delete時レコードが削除され204が返る()
    {
        Category::factory()->count(5)->create();
        $contact = Contact::factory()->create([
            'first_name' => 'テスト',
            'email' => 'test@test',
        ]);

        $tags = Tag::factory()->count(5)->create();

        $rand = mt_rand(1, 5);
        $tagIds = $tags->random($rand)->pluck('id')->toArray();

        $contact->tags()->attach($tagIds);
        $response = $this->deleteJson('/api/v1/contacts/' . $contact->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('contacts', [
            'first_name' => 'テスト',
            'email' => 'test@test',
        ]);
        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
        ]);
    }

    /** @test */
    public function ap_iで_delete時存在しない_i_dで404が返る()
    {
        $response = $this->deleteJson('/api/v1/contacts/99');

        $response->assertStatus(404);
    }
}
