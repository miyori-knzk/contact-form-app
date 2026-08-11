<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Tag;





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
        $url = '/admin?'. $urlQuery;

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
        $url = '/admin?'. $urlQuery;

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

        $tmpContact = array(
            'first_name' => 'テスト名',
            'last_name' => 'テスト姓',
            'gender' => 1,
            'email' => 'test@test',
            'tel' => '09012345678',
            'address' => '沖縄県',
            'category_id' => $category->id,
            'tag_ids' => array(1,2),
            'detail' => 'テスト内容',
        );

        $responce = $this->actingAs($user)->post(route('contacts.store'), $tmpContact);
        $responce->assertSessionHasNoErrors();
    }

    /** @test */
    public function 問い合わせ作成時に不正な電話番号形式を拒否する()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->count(2)->create();

        $tmpContact = array(
            'first_name' => 'テスト名',
            'last_name' => 'テスト姓',
            'gender' => 1,
            'email' => 'test@test',
            'tel' => '090123456',
            'address' => '沖縄県',
            'category_id' => $category->id,
            'tag_ids' => array(1,2),
            'detail' => 'テスト内容',
        );

        $responce = $this->actingAs($user)->post(route('contacts.store'), $tmpContact);
        $responce->assertSessionHasErrors('tel');
    }

    /** @test */
    public function タグ作成時のタグ名がない場合はバリデーションエラーになる()
    {
        $user = User::factory()->create();

        $tmpTag = array(
            'name' => '',
        );

        $responce = $this->actingAs($user)->post(route('admin.tags.store'), $tmpTag);
        $responce->assertSessionHasErrors('name');
    }

    /** @test */
    public function タグ作成時のタグ名が50文字以上の場合バリデーションエラーになる()
    {
        $user = User::factory()->create();

        $tmpTag = array(
            'name' => str_repeat('あ', 51),
        );

        $responce = $this->actingAs($user)->post(route('admin.tags.store'), $tmpTag);
        $responce->assertSessionHasErrors('name');
    }

    /** @test */
    public function タグ作成時のタグ名が重複している場合バリデーションエラーになる()
    {
        $user = User::factory()->create();
        Tag::factory()->create(['name' => 'テストタグ']);

        $tmpTag = array(
            'name' => 'テストタグ',
        );

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
}
