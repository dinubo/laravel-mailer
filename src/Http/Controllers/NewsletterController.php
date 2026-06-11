<?php

namespace Dinubo\Mailer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Dinubo\Mailer\Http\Requests\NewsletterRequest;
use Dinubo\Mailer\Mailer;
use Dinubo\Mailer\Models\Newsletter;

class NewsletterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortable = [
            'id' => 'id',
            'segment' => 'segment',
            'event' => 'event',
            'action' => 'action',
            'category' => 'category',
            'subject' => 'subject',
            'scheduled' => 'after_sec',
            'rate' => 'daily_rate',
            'status' => 'is_active',
        ];

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'sort' => ['nullable', Rule::in(array_keys($sortable))],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $sort = $this->preference($request, 'sort', array_keys($sortable)) ?? 'category';
        $dir = $this->preference($request, 'dir', ['asc', 'desc']) ?? 'asc';

        $newsletters = Newsletter::orderBy($sortable[$sort], $dir)
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('mailer::newsletters.index', compact('newsletters', 'sort', 'dir'));
    }

    /**
     * @param list<string> $allowed
     */
    private function preference(Request $request, string $param, array $allowed): ?string
    {
        if ($request->filled($param)) {
            $value = $request->query($param);
            session(["mailer.$param" => $value]);

            return $value;
        }

        $value = session("mailer.$param");

        return in_array($value, $allowed, true) ? $value : null;
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
    public function show(Request $request, Newsletter $newsletter)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

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
