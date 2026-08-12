<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $tagArr = [];

        $tmpTel = $request->tel1 . '-' . $request->tel2 . '-' . $request->tel3;

        $tagArr['tag_ids'] = $request->tag_ids;
        $validated = array_merge($request->validated(), $tagArr);
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

        // 入力値保持
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

        // 修正ボタンが押された時の処理
        if ($request->back == 'back') {
            return redirect('?' . $urlQuery);
        }

        // 送信時の処理
        // tagのバリデーション追加
        $tagValidation = Validator::make($request->all(), [
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        if ($tagValidation->fails()) {
            return redirect('?' . $urlQuery);
        }

        $contact = Contact::create($request->validated());

        if (count($tagValidation->validated()) > 0) {
            $tagIds = $tagValidation->validated()['tag_ids'];
            $contact->tags()->attach($tagIds);
        }

        return redirect()->route('contacts.thanks');
    }

    public function thanks()
    {
        return view('contact.thanks');
    }

    public function export(ExportContactRequest $request): StreamedResponse
    {
        $filename = 'contacts_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($request) {

            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', '氏名', '性別', 'メール', '電話', '住所', '建物', 'カテゴリ', '内容', '作成日時']);
            $contacts = Contact::makeQuery($request)->with('category')->orderBy('created_at', 'desc')->get();

            foreach ($contacts as $contact) {
                fputcsv($handle, [
                    $contact->id,
                    $contact->last_name . '　' . $contact->first_name,
                    $contact->gender_label,
                    $contact->email,
                    $contact->tel,
                    $contact->address,
                    $contact->building,
                    $contact->category->content,
                    $contact->detail,
                    $contact->created_at,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
