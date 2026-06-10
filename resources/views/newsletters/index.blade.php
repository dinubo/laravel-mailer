@extends('mailer::layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

<div class="container">

    <div class="section">

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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('newsletter-statistics-chart');

    if (! canvas) {
        return;
    }

    fetch("{{ route('mailer.newsletters.statistics.index') }}")
        .then(function (response) {
            if (! response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            return response.json();
        })
        .then(drawChart)
        .catch(function (error) {
            console.error('Error loading chart data', error);
            alert('Error loading chart data: ' + error.message);
        });

    function drawChart(chartData) {
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

        new Chart(canvas, {
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
});
</script>
@endpush
