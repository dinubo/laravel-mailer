@extends('mailer::layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

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

        {{ $newsletters->links() }}

        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>
                        Segment
                    </th>
                    <th>
                        Event
                    </th>
                    <th>
                        Action
                    </th>
                    <th>
                        Category
                    </th>
                    <th>
                        Subject
                    </th>
                    <th>
                        Scheduled
                    </th>
                    <th>
                        Rate
                    </th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($newsletters as $newsletter)
                    <tr>
                        <th scope="row">{{ $newsletter->id }}</th>
                        <td>{{ $newsletter->segment ?? '-' }}</td>
                        <td>{{ $newsletter->event ?? '-' }}</td>
                        <td>{{ $newsletter->action ?? '-' }}</td>
                        <td>{{ $newsletter->category ?? '-' }}</td>
                        <td>{{ $newsletter->subject }}</td>
                        <td>{{ $newsletter->after }}</td>
                        <td>{{ $newsletter->daily_rate }}</td>
                        <td>{{ $newsletter->is_active ? 'Active' : 'Inactive' }}</td>
                        <td>
                            <a href="{{ route('mailer.newsletters.show', $newsletter) }}" class="btn btn-info text-white">
                                <i class="material-icons">arrow_forward</i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $newsletters->links() }}

    </div>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('newsletter-statistics-chart');
    var rangeInput = document.getElementById('chart-range');

    if (! canvas) {
        return;
    }

    var endpoint = "{{ route('mailer.newsletters.statistics.index') }}";

    var colors = [
        '255, 99, 132',
        '54, 162, 235',
        '255, 206, 86',
        '75, 192, 192',
        '153, 102, 255',
        '255, 159, 64',
        '201, 203, 207',
        '255, 117, 99',
    ];

    var chart;

    // Default window: the last 21 days (today - 20 ... today).
    var end = new Date();
    var start = new Date();
    start.setDate(end.getDate() - 20);

    function fmt(d) {
        var month = ('0' + (d.getMonth() + 1)).slice(-2);
        var day = ('0' + d.getDate()).slice(-2);
        return d.getFullYear() + '-' + month + '-' + day;
    }

    function load(from, to) {
        fetch(endpoint + '?from=' + from + '&to=' + to)
            .then(function (response) {
                if (! response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            })
            .then(render)
            .catch(function (error) {
                console.error('Error loading chart data', error);
                alert('Error loading chart data: ' + error.message);
            });
    }

    function render(chartData) {
        var datasets = chartData.datasets.map(function (dataset, i) {
            return {
                label: dataset.label,
                data: dataset.data,
                fill: false,
                backgroundColor: 'rgba(' + colors[i % colors.length] + ', 0.2)',
                borderColor: 'rgba(' + colors[i % colors.length] + ', 1)',
                borderWidth: 1,
            };
        });

        if (chart) {
            chart.data.labels = chartData.labels;
            chart.data.datasets = datasets;
            chart.update();

            return;
        }

        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: datasets,
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    }

    if (rangeInput && window.flatpickr) {
        flatpickr(rangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: [start, end],
            onChange: function (selectedDates) {
                if (selectedDates.length === 2) {
                    load(fmt(selectedDates[0]), fmt(selectedDates[1]));
                }
            },
        });
    }

    load(fmt(start), fmt(end));
});
</script>
@endpush
