<?php

namespace Dinubo\Mailer\Http\Controllers;

use Illuminate\Routing\Controller;
use Dinubo\Mailer\Models\Newsletter;

class NewsletterStatisticsController extends Controller
{
    public function index()
    {
        $chart_data = Newsletter::statistics();

        return response()->json($chart_data);
    }

    public function show(Newsletter $newsletter)
    {
        $chart_data = $newsletter->getStatistics();

        return response()->json($chart_data);
    }
}
