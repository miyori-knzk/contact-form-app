<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Contact;
use App\Models\Category;
use App\Models\ContactTag;
use App\Models\Tag;



class ModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function １つのカテゴリから、紐づく複数のお問い合わせを取得できる(): void
    {
        $category = Category::factory()->create();
        $contacs = Contact::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertCount(3, $category->contacts);
    }

    /** @test */
    public function １つのカテゴリが特定のカテゴリに属し、複数のタグと同期できる(): void
    {
        $category = Category::factory()->create();
        Tag::factory()->count(3)->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $contact->tags()->attach(1);
        $contact->tags()->attach(2);
        $contact->tags()->attach(3);

        Tag::find(1)->delete();

        $tags = Tag::all()->pluck('id');

        $contact->tags()->sync($tags);


        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag,
            ]);
        }
        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => 1,
        ]);
    }

    /** @test */
    public function １つのタグから複数のお問い合わせを中間テーブルを介して取得できる(): void
    {
        Category::factory()->create();
        $tag = Tag::factory()->create();
        $contacts = Contact::factory()->count(3)->create();


        foreach($contacts as $contact){
            $contact->tags()->attach($tag->id);
        }

         $this->assertCount(3, $tag->contacts);
    }
}
