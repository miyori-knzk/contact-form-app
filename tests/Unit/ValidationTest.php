<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 問い合わせ一覧検索のバリデーションが有効である()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $contact = Contact::factory()->create();

        $data = [
            'keyword' => 'キーワード',
            'gender' => 1,
            'category_id' => $contact->id,
            'date' => date('Y-m-d'),
        ];

        $urlQuery = http_build_query($data);
        $url = '/admin?' . $urlQuery;

        $responce = $this->actingAs($user)->get($url);
        $responce->assertStatus(200);
    }

    /** @test */
    public function 問い合わせ一覧検索で不正な性別値を拒否する()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $contact = Contact::factory()->create();

        $data = [
            'keyword' => 'キーワード',
            'gender' => 99,
            'category_id' => $contact->id,
            'date' => date('Y-m-d'),
        ];

        $urlQuery = http_build_query($data);
        $url = '/admin?' . $urlQuery;

        $responce = $this->actingAs($user)->get($url);

        $responce->assertStatus(302);
        $responce->assertSessionHasErrors('gender');
    }

    /** @test */
    public function 問い合わせ作成時のバリデーションが有効である()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->count(2)->create();

        $tmpContact = [
            'first_name' => 'テスト名',
            'last_name' => 'テスト姓',
            'gender' => 1,
            'email' => 'test@test',
            'tel' => '09012345678',
            'address' => '沖縄県',
            'category_id' => $category->id,
            'tag_ids' => [1, 2],
            'detail' => 'テスト内容',
        ];

        $responce = $this->actingAs($user)->post(route('contacts.store'), $tmpContact);
        $responce->assertSessionHasNoErrors();
    }

    /** @test */
    public function 問い合わせ作成時に不正な電話番号形式を拒否する()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->count(2)->create();

        $tmpContact = [
            'first_name' => 'テスト名',
            'last_name' => 'テスト姓',
            'gender' => 1,
            'email' => 'test@test',
            'tel' => '090123456',
            'address' => '沖縄県',
            'category_id' => $category->id,
            'tag_ids' => [1, 2],
            'detail' => 'テスト内容',
        ];

        $responce = $this->actingAs($user)->post(route('contacts.store'), $tmpContact);
        $responce->assertSessionHasErrors('tel');
    }

    /** @test */
    public function タグ作成時のタグ名がない場合はバリデーションエラーになる()
    {
        $user = User::factory()->create();

        $tmpTag = [
            'name' => '',
        ];

        $responce = $this->actingAs($user)->post(route('admin.tags.store'), $tmpTag);
        $responce->assertSessionHasErrors('name');
    }

    /** @test */
    public function タグ作成時のタグ名が50文字以上の場合バリデーションエラーになる()
    {
        $user = User::factory()->create();

        $tmpTag = [
            'name' => str_repeat('あ', 51),
        ];

        $responce = $this->actingAs($user)->post(route('admin.tags.store'), $tmpTag);
        $responce->assertSessionHasErrors('name');
    }

    /** @test */
    public function タグ作成時のタグ名が重複している場合バリデーションエラーになる()
    {
        $user = User::factory()->create();
        Tag::factory()->create(['name' => 'テストタグ']);

        $tmpTag = [
            'name' => 'テストタグ',
        ];

        $responce = $this->actingAs($user)->post(route('admin.tags.store'), $tmpTag);
        $responce->assertSessionHasErrors('name');
    }

    /** @test */
    public function 他で使われているタグ名に変更しようとするとバリデーションエラーになる()
    {
        $user = User::factory()->create();
        $usedTag = Tag::factory()->create(['name' => 'テストタグ1']);
        $updateTag = Tag::factory()->create(['name' => 'テストタグ2']);

        $responce = $this->actingAs($user)->put(route('admin.tags.update', $updateTag), [
            'name' => 'テストタグ1',
        ]);
        $responce->assertSessionHasErrors('name');
    }

    /** @test */
    public function タグ変更時に自分が使っているタグ名を維持できる()
    {
        $user = User::factory()->create();
        $usedTag = Tag::factory()->create(['name' => 'テストタグ1']);
        $updateTag = Tag::factory()->create(['name' => 'テストタグ2']);

        $responce = $this->actingAs($user)->put(route('admin.tags.update', $updateTag), [
            'name' => 'テストタグ2',
        ]);
        $responce->assertSessionHasNoErrors();
    }

    /** @test */
    public function cs_v出力時に正しいフィルタ条件を受け付ける()
    {
        Category::factory()->count(5)->create();
        $tags = Tag::factory()->count(5)->create();

        $contacts = Contact::factory()->count(50)->create();
        $contactData = Contact::factory()->create([
            'first_name' => 'テスト名',
            'gender' => 1,
            'category_id' => 2,
        ]);

        foreach ($contacts as $contact) {
            $rand = mt_rand(1, 3);
            $contact->tags()->attach($tags->random($rand)->pluck('id')->toArray());
        }

        $serchDate = date('Y-m-d');
        $urlQuery = http_build_query([
            'keyword' => 'テスト名',
            'gender' => 1,
            'category_id' => 2,
            'date' => 2,
        ]);

        $response = $this->get('/contacts/export?' . $urlQuery);
        $response->assertStatus(302);
    }

    /** @test */
    public function cs_v出力時に不正な性別を拒否する()
    {
        Category::factory()->count(5)->create();
        $tags = Tag::factory()->count(5)->create();

        $contacts = Contact::factory()->count(50)->create();
        $contactData = Contact::factory()->create([
            'first_name' => 'テスト名',
            'gender' => 1,
            'category_id' => 2,
        ]);

        foreach ($contacts as $contact) {
            $rand = mt_rand(1, 3);
            $contact->tags()->attach($tags->random($rand)->pluck('id')->toArray());
        }

        $serchDate = date('Y-m-d');
        $urlQuery = http_build_query([
            'keyword' => 'テスト名',
            'gender' => 99,
            'category_id' => 2,
            'date' => 2,
        ]);

        $response = $this->get('/contacts/export?' . $urlQuery);

        $response->assertSessionHasErrors('gender');
    }

    /** @test */
    public function cs_v出力時に存在しないカテゴリ_i_dを拒否する()
    {
        Category::factory()->count(5)->create();
        $tags = Tag::factory()->count(5)->create();

        $contacts = Contact::factory()->count(50)->create();
        $contactData = Contact::factory()->create([
            'first_name' => 'テスト名',
            'gender' => 1,
            'category_id' => 2,
        ]);

        foreach ($contacts as $contact) {
            $rand = mt_rand(1, 3);
            $contact->tags()->attach($tags->random($rand)->pluck('id')->toArray());
        }

        $serchDate = date('Y-m-d');
        $urlQuery = http_build_query([
            'keyword' => 'テスト名',
            'gender' => 1,
            'category_id' => 99,
            'date' => $serchDate,
        ]);

        $response = $this->get('/contacts/export?' . $urlQuery);

        $response->assertSessionHasErrors('category_id');
    }

    /** @test */
    public function ap_i検索時に_index_contact_requestのフィルタが有効である()
    {
        $category = Category::factory()->create();
        $contacts = Contact::factory()->count(20)->create();
        $tags = Tag::factory()->count(5)->create();

        foreach ($contacts as $contact) {
            $rand = mt_rand(1, 5);
            $contact->tags()->attach($tags->random($rand)->pluck('id')->toArray());
        }

        $response = $this->getJson('/api/v1/contacts');

        $response->assertJsonMissingValidationErrors(['keyword',  'gender', 'category_id', 'date', 'page', 'per_page']);
        $response->assertOk();
    }

    /** @test */
    public function ap_i検索時に_index_contact_requestで不正な値を拒否する()
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->create();

        $data = [
            'keyword' => str_repeat('あ', 256),
            'gender' => 99,
            'category_id' => 99,
            'date' => 'abc',
            'page' => 'first',
            'per_page' => 101,
        ];

        $urlQuery = http_build_query($data);
        $url = '/api/v1/contacts?' . $urlQuery;

        $response = $this->getJson($url);

        $response->assertJsonValidationErrors(['keyword',  'gender', 'category_id', 'date', 'page', 'per_page']);
    }

    /** @test */
    public function ap_i作成時に_index_contact_requestの全必須項目・タグ入力を受け付けること()
    {
        $tagIds = [];
        $category = Category::factory()->create();
        $contact = Contact::factory()->raw();
        $tags = Tag::factory()->count(5)->create();

        $rand = mt_rand(1, 5);
        $tagIds['tag_ids'] = $tags->random($rand)->pluck('id')->toArray();

        $response = $this->postJson('/api/v1/contacts', array_merge($contact, $tagIds));

        $response->assertJsonMissingValidationErrors();
    }

    /** @test */
    public function ap_i作成時に_index_contact_requestで不正な値を拒否すること()
    {
        $contact = [];

        $category = Category::factory()->create();
        $contact = Contact::factory()->raw();

        $contact['last_name'] = str_repeat('あ', 256);
        $contact['first_name'] = 111;
        $contact['gender'] = 99;
        $contact['email'] = 99;
        $contact['tel'] = 999;
        $contact['address'] = str_repeat('あ', 256);
        $contact['building'] = str_repeat('あ', 256);
        $contact['building'] = str_repeat('あ', 256);
        $contact['category_id'] = 99;
        $contact['detail'] = str_repeat('あ', 121);
        $contact['tag_ids'] = 99;

        $response = $this->postJson('/api/v1/contacts', $contact);

        $response->assertJsonValidationErrors(['last_name', 'first_name', 'gender', 'email', 'tel', 'address', 'building', 'category_id', 'detail', 'tag_ids']);
    }
}
