<?php

namespace Dinubo\Mailer\Http\Controllers;

use Illuminate\Routing\Controller;
use Dinubo\Mailer\Http\Requests\NewsletterRequest;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Newsletter;

class NewsletterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $newsletters = Newsletter::orderBy('category')->paginate(100);

        return view('mailer::newsletters.index', compact('newsletters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $newsletter = null;

        if (request()->has('from')) {
            $newsletter = Newsletter::find(request()->from);
        }

        if (!$newsletter) {
            $newsletter = new Newsletter();
        }

        $newsletter->is_active = false;

        $placeholders = Mailer::getPlaceholders();

        $segments = Mailer::getSegments();

        $events = Mailer::getEvents();

        $actions = Mailer::getActions();

        return view('mailer::newsletters.create', compact('newsletter', 'placeholders', 'segments', 'events', 'actions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NewsletterRequest $request)
    {
        $newsletter = new Newsletter($request->validated());

        $newsletter->save();

        return redirect()->route('mailer.newsletters.show', $newsletter);
    }

    /**
     * Display the specified resource.
     */
    public function show(Newsletter $newsletter)
    {
        return view('mailer::newsletters.show', compact('newsletter'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Newsletter $newsletter)
    {
        $for_deletion = request()->exists('delete');

        $placeholders = Mailer::getPlaceholders();

        $segments = Mailer::getSegments();

        $events = Mailer::getEvents();

        $actions = Mailer::getActions();

        return view('mailer::newsletters.edit', compact('newsletter', 'for_deletion', 'placeholders', 'segments', 'events', 'actions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NewsletterRequest $request, Newsletter $newsletter)
    {
        $newsletter->fill($request->validated());

        $newsletter->save();

        return redirect()->route('mailer.newsletters.show', $newsletter);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Newsletter $newsletter)
    {
        $newsletter->delete();

        return redirect()->route('mailer.newsletters.index');
    }
}
