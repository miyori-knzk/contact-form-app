<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 認証済みユーザーはタグの編集画面を表示できる(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.tags.edit', $tag));

        $response->assertStatus(200);
        $response->assertViewHas('tag');

    }

    /** @test */
    public function 認証済みユーザーはタグを作成できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.tags.store'), [
            'name' => 'テストタグ',
        ]);

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseHas('tags', [
            'name' => 'テストタグ',
        ]);

    }

    /** @test */
    public function 認証済みユーザーはタグを更新できる(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.tags.update', $tag), [
            'name' => '更新後のタグ',
        ]);

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseHas('tags', [
            'name' => '更新後のタグ',
        ]);
    }

    /** @test */
    public function 認証済みユーザーはタグを削除できる(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->delete(route('admin.tags.destroy', $tag));

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function 未認証ユーザーはタグを作成できない(): void
    {
        $response = $this->post(route('admin.tags.store'), [
            'name' => 'テストタグ',
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーはタグの編集画面を表示できない(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->get(route('admin.tags.edit', $tag));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーはタグを更新できない(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->put(route('admin.tags.update', $tag), [
            'name' => '更新後のタグ',
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーはタグを削除できない(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->delete(route('admin.tags.destroy', $tag));

        $response->assertRedirect(route('login'));
    }
}
