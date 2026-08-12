<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexContactRequest $request): AnonymousResourceCollection
    {
        $perPage = 20;
        if ($request->filled('per_page')) {
            $perPage = $request->per_page;
        }
        $contacts = Contact::makeQuery($request)->with(['category', 'tags'])->latest()->paginate($perPage);

        return ContactResource::collection($contacts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContactRequest $request)
    {
        JsonResource::wrap('ContactResource');

        $contact = Contact::create($request->validated());

        $validated = $request->validated();
        if ($validated->tag_ids > 0) {
            $tagIds = $validated->tag_ids;
            $contact->tags()->attach($tagIds);
        }

        return responce()->json($contact->load(['category', 'tag'], 201));
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact): ContactResource
    {
        JsonResource::wrap('ContactResource');

        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
