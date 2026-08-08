<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\Tag;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    public function confirm(StoreContactRequest $request)
    {
        $tags = null;

        $tmpTel = $request->tel1 . '-' . $request->tel2 . '-' . $request->tel3;

        $validated = $request->validated();
        $category = Category::findOrFail($validated['category_id']);
        if ($request->filled('tag_ids')) {
            $tags = Tag::whereIn('id', $request->tag_ids)->get();
        }

        return view('contact.confirm', compact('validated', 'category', 'tmpTel', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContactRequest $request)
    {
        $tmpContactTags = [];

        // 修正ボタンが押された時の処理
        if ($request->back == 'back') {
            $queryArray = [
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'gender' => 2,
                'email' => $request->email,
                'tel' => $request->tmpTel,
                'address' => $request->address,
                'building' => $request->building,
                'category_id' => $request->category_id,
                'tag_ids' => $request->tag_ids,
                'detail' => $request->detail,
            ];
            $urlQuery = http_build_query($queryArray);

            return redirect('?' . $urlQuery);
        }

        $contact = Contact::create($request->validated());
        // dd($contact);

        if ($request->filled('tag_ids')) {
            foreach ($request->tag_ids as $tag) {
                $tmpContactTags[] = [
                    'contact_id' => $contact->id,
                    'tag_id' => $tag,
                ];
            }
        }

        if (count($tmpContactTags) != 0) {
            foreach ($tmpContactTags as $tmpContactTag) {
                $newContactTag = new ContactTag;
                $newContactTag->contact_id = $tmpContactTag['contact_id'];
                $newContactTag->tag_id = $tmpContactTag['tag_id'];
                $newContactTag->save();
            }
        }

        return redirect()->route('contacts.thanks');
    }

    public function thanks()
    {
        return view('contact.thanks');
    }
}
