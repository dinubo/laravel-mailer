@extends('mailer::layouts.app')

@section('content')
<div class="container">

    <div class="section">

        <div class="form-inline mb-3">
            <label class="mr-2 mb-0" for="chart-range">Date range</label>
            <input type="text" id="chart-range" class="form-control" autocomplete="off" style="max-width: 240px;">
        </div>

        <canvas id="newsletter-statistics-chart" width="400" height="120"></canvas>

        <h2 class="mt-6">
            Newsletters

            <a href="{{ route('mailer.newsletters.create') }}" class="btn btn-primary text-white">
                <i class="material-icons">add_circle</i>
                Create
            </a>
        </h2>

        <div class="js-pagination">{{ $newsletters->links() }}</div>

        <table class="table">
            <thead>
                <tr>
                    <th>@include('mailer::newsletters._sortable_header', ['column' => 'id', 'label' => '#'])</th>
                    <th>@include('mailer::newsletters._sortable_header', ['column' => 'segment', 'label' => 'Segment'])</th>
                    <th>@include('mailer::newsletters._sortable_header', ['column' => 'event', 'label' => 'Event'])</th>
                    <th>@include('mailer::newsletters._sortable_header', ['column' => 'action', 'label' => 'Action'])</th>
                    <th>@include('mailer::newsletters._sortable_header', ['column' => 'category', 'label' => 'Category'])</th>
                    <th>@include('mailer::newsletters._sortable_header', ['column' => 'subject', 'label' => 'Subject'])</th>
                    <th>@include('mailer::newsletters._sortable_header', ['column' => 'scheduled', 'label' => 'Scheduled'])</th>
                    <th>@include('mailer::newsletters._sortable_header', ['column' => 'rate', 'label' => 'Rate'])</th>
                    <th>@include('mailer::newsletters._sortable_header', ['column' => 'status', 'label' => 'Status'])</th>
                    <th>Sent</th>
                    <th>Clicks</th>
                    <th>Unsubscribes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($newsletters as $newsletter)
                    <tr data-newsletter-id="{{ $newsletter->id }}">
                        <th scope="row">{{ $newsletter->id }}</th>
                        <td>{{ $newsletter->segment ?? '-' }}</td>
                        <td>{{ $newsletter->event ?? '-' }}</td>
                        <td>{{ $newsletter->action ?? '-' }}</td>
                        <td>{{ $newsletter->category ?? '-' }}</td>
                        <td>{{ $newsletter->subject }}</td>
                        <td>{{ $newsletter->after }}</td>
                        <td>{{ $newsletter->daily_rate }}</td>
                        <td>{{ $newsletter->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="js-sent">…</td>
                        <td class="js-clicked">…</td>
                        <td class="js-unsubscribe">…</td>
                        <td>
                            <a href="{{ route('mailer.newsletters.show', $newsletter) }}" class="btn btn-info text-white">
                                <i class="material-icons">arrow_forward</i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="js-pagination">{{ $newsletters->links() }}</div>

    </div>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush

@push('scripts')
    @include('mailer::newsletters._statistics', ['endpoint' => route('mailer.newsletters.statistics.index')])
@endpush
