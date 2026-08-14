<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
        $tagIds = [];

        $validated = $request->validated();
        $contact = Contact::create($request->validated());
        $tagIds = $validated['tag_ids'];

        $contact->tags()->attach($tagIds);

        return (new ContactResource($contact))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact): ContactResource
    {
        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreContactRequest $request, Contact $contact)
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'];
        $contact->update($validated);
        $contact->tags()->sync($tagIds);
        $contact->load(['category', 'tags']);

        return (new ContactResource($contact))->response()->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->json(null, 204);
    }
}
