<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function お問い合わせフォーム入力ページが表示できる(): void
    {
        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->get(route('contacts.index'));

        $response->assertStatus(200);
        $response->assertViewHas('tags');
        $response->assertViewHas('categories');
    }

    /** @test */
    public function サンクスページが表示できる(): void
    {
        $response = $this->get(route('contacts.thanks'));

        $response->assertStatus(200);
    }

    /** @test */
    public function バリデーション通過時にお問い合わせフォーム確認ページが表示できる(): void
    {
        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();
        $contactArray = Contact::factory()->raw();

        $response = $this->post(route('contacts.confirm'), $contactArray);

        $response->assertStatus(200);
    }

    /** @test */
    public function 姓が空だとバリデーションエラーになりお問い合わせフォーム入力ページにリダイレクトされる(): void
    {
        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.confirm'), [
            'last_name' => '',
        ]);

        $response->assertSessionHasErrors('last_name');
        $response->assertRedirect(route('contacts.index'));
    }

    /** @test */
    public function 名が空だとバリデーションエラーになりお問い合わせフォーム入力ページにリダイレクトされる(): void
    {
        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.confirm'), [
            'first_name' => '',
        ]);

        $response->assertSessionHasErrors('first_name');
        $response->assertRedirect(route('contacts.index'));
    }

    /** @test */
    public function 性別が未選択だとバリデーションエラーになりお問い合わせフォーム入力ページにリダイレクトされる(): void
    {
        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.confirm'), [
            'gender' => '',
        ]);

        $response->assertSessionHasErrors('gender');
        $response->assertRedirect(route('contacts.index'));
    }

    /** @test */
    public function メールアドレスが未入力だとバリデーションエラーになりお問い合わせフォーム入力ページにリダイレクトされる(): void
    {
        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.confirm'), [
            'email' => '',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertRedirect(route('contacts.index'));
    }

    /** @test */
    public function メール形式が不正だとバリデーションエラーになりお問い合わせフォーム入力ページにリダイレクトされる(): void
    {
        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.confirm'), [
            'email' => 'test.com',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertRedirect(route('contacts.index'));
    }

    /** @test */
    public function 電話番号が未入力だとバリデーションエラーになりお問い合わせフォーム入力ページにリダイレクトされる(): void
    {

        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.confirm'), [
            'tel' => '',
        ]);

        $response->assertSessionHasErrors('tel');
        $response->assertRedirect(route('contacts.index'));
    }

    /** @test */
    public function 住所が未入力だとバリデーションエラーになりお問い合わせフォーム入力ページにリダイレクトされる(): void
    {

        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.confirm'), [
            'address' => '',
        ]);

        $response->assertSessionHasErrors('tel');
        $response->assertRedirect(route('contacts.index'));
    }

    /** @test */
    public function お問い合わせの種類が未選択だとバリデーションエラーになりお問い合わせフォーム入力ページにリダイレクトされる(): void
    {

        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.confirm'), [
            'category_id' => '',
        ]);

        $response->assertSessionHasErrors('category_id');
        $response->assertRedirect(route('contacts.index'));
    }

    /** @test */
    public function お問い合わせ内容が未入力だとバリデーションエラーになりお問い合わせフォーム入力ページにリダイレクトされる(): void
    {

        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.confirm'), [
            'detail' => '',
        ]);

        $response->assertSessionHasErrors('detail');
        $response->assertRedirect(route('contacts.index'));
    }

    /** @test */
    public function お問い合わせ内容が120文字以上だとバリデーションエラーになりお問い合わせフォーム入力ページにリダイレクトされる(): void
    {

        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.confirm'), [
            'detail' => str_repeat('あ', 121),
        ]);

        $response->assertSessionHasErrors('detail');
        $response->assertRedirect(route('contacts.index'));
    }

    /** @test */
    public function 確認画面からのバリデーション通過時にレコードが保存されサンクスページが表示できる(): void
    {
        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $contactArray = Contact::factory()->raw();
        $contactArray['last_name'] = 'テスト名';
        $contactArray['detail'] = 'テスト';

        $response = $this->post(route('contacts.store'), $contactArray);

        $response->assertRedirect(route('contacts.thanks'));
        $this->assertDatabaseHas('contacts', [
            'last_name' => 'テスト名',
            'detail' => 'テスト',
        ]);
    }

    /** @test */
    public function 確認画面で、タグが数値でなかった場合バリデーションエラーとなりリダイレクトされる(): void
    {
        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.store'), [
            'first_name' => 'テスト名',
            'detail' => 'テスト',
            'tag_ids' => '文字',
        ]);

        $response->assertRedirect(route('contacts.index'));
        $this->assertDatabaseMissing('contacts', [
            'first_name' => 'テスト名',
            'detail' => 'テスト',
        ]);
    }

    /** @test */
    public function 確認画面で、タグが存在しない値だった場合バリデーションエラーとなりリダイレクトされる(): void
    {
        Category::factory()->count(3)->create();
        Tag::factory()->count(3)->create();

        $response = $this->post(route('contacts.store'), [
            'first_name' => 'テスト名',
            'detail' => 'テスト',
            'tag_ids' => 99,
        ]);

        $response->assertRedirect(route('contacts.index'));
        $this->assertDatabaseMissing('contacts', [
            'first_name' => 'テスト名',
            'detail' => 'テスト',
        ]);
    }

    /** @test */
    public function ログイン済みの管理者がフィルタ条件付きで_cs_vを_d_lできる()
    {
        $user = User::factory()->create();
        Category::factory()->count(5)->create();
        $tags = Tag::factory()->count(5)->create();
        $contactData = Contact::factory()->create([
            'last_name' => 'テスト姓',
            'first_name' => 'テスト名',
            'email' => 'test@test',
            'gender' => 1,
            'category_id' => 2,
        ]);
        $contacts = Contact::factory()->count(50)->create();

        foreach ($contacts as $contact) {
            $rand = mt_rand(1, 3);
            $contact->tags()->attach($tags->random($rand)->pluck('id')->toArray());
        }

        $serchDate = date('Y-m-d');
        $urlQuery = http_build_query([
            'keyword' => 'test',
            'gender' => 1,
            'category_id' => 2,
            'date' => $serchDate,
        ]);

        $response = $this->actingAs($user)->get('/contacts/export?' . $urlQuery);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        $dataCnt = count(array_filter(explode("\n", $content))) - 1;

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('test@test', $content);
        $this->assertEquals(1, $dataCnt);
    }

    /** @test */
    public function ログイン済みの管理者が無指定時は新着順で_cs_vを_d_lできる()
    {
        $user = User::factory()->create();
        Category::factory()->count(5)->create();
        $tags = Tag::factory()->count(5)->create();
        $contacts = Contact::factory()->count(50)->create();

        foreach ($contacts as $contact) {
            $rand = mt_rand(1, 3);
            $contact->tags()->attach($tags->random($rand)->pluck('id')->toArray());
        }

        $response = $this->actingAs($user)->get('/contacts/export?');

        ob_start();
        $response->sendContent();
        $contents = ob_get_clean();
        $contentsArr = array_filter(explode("\n", $contents));

        $firstData = $contentsArr[1];
        $lastData = $contentsArr[50];

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertEquals(50, count($contentsArr) - 1);
        $this->assertStringContainsString('1', array_filter(explode(',', $lastData))[0]);
        $this->assertStringContainsString('50', array_filter(explode(',', $firstData))[0]);
    }
}
