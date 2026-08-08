<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = Category::all();
        $tags = Tag::all();

        $contacts = Contact::makeQuery($request)->with('category', 'tags')->paginate(7);

        return view('admin.index', compact('categories', 'contacts', 'tags'));
    }

    public function show($contactId)
    {
        $contact = Contact::with('tags')->find($contactId);

        return view('admin.show', compact('contact'));
    }
}
