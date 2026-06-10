<?php

namespace Dinubo\Mailer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Dinubo\Mailer\Models\Newsletter;

class NewsletterStatisticsController extends Controller
{
    public function index(Request $request)
    {
        $range = $this->validateRange($request);

        $chart_data = Newsletter::statistics(null, $range['from'] ?? null, $range['to'] ?? null);

        return response()->json($chart_data);
    }

    public function show(Request $request, Newsletter $newsletter)
    {
        $range = $this->validateRange($request);

        $chart_data = $newsletter->getStatistics($range['from'] ?? null, $range['to'] ?? null);

        return response()->json($chart_data);
    }

    /**
     * Validate the optional ?from / ?to query parameters. Both are optional
     * (absent => the model's default 21-day window); when given they must be
     * valid dates with to >= from.
     */
    private function validateRange(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
    }
}
