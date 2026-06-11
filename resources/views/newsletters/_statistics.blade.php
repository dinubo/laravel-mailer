{{--
    Shared statistics chart for the newsletters index and show pages.

    Expects a `$endpoint` variable (the statistics JSON URL). The endpoint returns
    { labels: [...dates], series: { "<newsletterId>"|"other": { send:[...], ... } } }.
    The chart sums every series into one dataset per metric; on the index page the
    per-newsletter Sent / Clicked columns read each row's own series by id (this is
    a no-op anywhere without those rows).
--}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('newsletter-statistics-chart');
    var rangeInput = document.getElementById('chart-range');
    var endpoint = "{{ $endpoint }}";

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

    var metrics = [
        { key: 'send',        label: 'Sent' },
        { key: 'open',        label: 'Opens' },
        { key: 'click',       label: 'Clicks' },
        { key: 'unsubscribe', label: 'Unsubscribes' },
        { key: 'bounce',      label: 'Bounces' },
        { key: 'drop',        label: 'Drops' },
        { key: 'spam',        label: 'Spam' },
    ];

    var chart;
    var rows = Array.prototype.slice.call(document.querySelectorAll('tr[data-newsletter-id]'));

    // Default window: the last 21 days (today - 20 ... today).
    var end = new Date();
    var start = new Date();
    start.setDate(end.getDate() - 20);

    function fmt(d) {
        var month = ('0' + (d.getMonth() + 1)).slice(-2);
        var day = ('0' + d.getDate()).slice(-2);
        return d.getFullYear() + '-' + month + '-' + day;
    }

    function sum(arr) {
        return (arr || []).reduce(function (total, value) { return total + (value || 0); }, 0);
    }

    // Format the count with its rate vs. the sent total on a second line:
    // "<count><br>(<rate>%)" (— when nothing was sent).
    function withRate(count, sent) {
        var rate = sent > 0 ? (count / sent * 100).toFixed(2) + '%' : '—';
        return count + '<br>(' + rate + ')';
    }

    // Collapse the per-newsletter series into one global dataset per metric by
    // summing every series' daily counts (aligned to labels).
    function buildDatasets(payload) {
        var labels = payload.labels || [];
        var series = payload.series || {};
        var keys = Object.keys(series);

        return metrics.map(function (metric, i) {
            var data = labels.map(function () { return 0; });

            keys.forEach(function (key) {
                var daily = series[key][metric.key];
                if (! daily) {
                    return;
                }
                for (var d = 0; d < data.length; d++) {
                    data[d] += daily[d] || 0;
                }
            });

            return {
                label: metric.label,
                data: data,
                fill: false,
                backgroundColor: 'rgba(' + colors[i % colors.length] + ', 0.2)',
                borderColor: 'rgba(' + colors[i % colors.length] + ', 1)',
                borderWidth: 1,
            };
        });
    }

    function renderChart(payload) {
        if (! canvas) {
            return;
        }

        var datasets = buildDatasets(payload);

        if (chart) {
            chart.data.labels = payload.labels;
            chart.data.datasets = datasets;
            chart.update();

            return;
        }

        chart = new Chart(canvas, {
            type: 'line',
            data: { labels: payload.labels, datasets: datasets },
            options: { scales: { y: { beginAtZero: true } } },
        });
    }

    // Fill each newsletter's Sent / Clicked columns from the same payload the
    // chart uses. No rows (e.g. the show page) => nothing to do.
    function fillTotals(payload) {
        var series = payload.series || {};

        rows.forEach(function (row) {
            var stats = series[row.dataset.newsletterId];
            var sent = stats ? sum(stats.send) : 0;
            var clicked = stats ? sum(stats.click) : 0;
            var unsubscribed = stats ? sum(stats.unsubscribe) : 0;

            var sentCell = row.querySelector('.js-sent');
            var clickedCell = row.querySelector('.js-clicked');
            var unsubscribeCell = row.querySelector('.js-unsubscribe');

            if (sentCell) {
                sentCell.textContent = sent;
            }
            if (clickedCell) {
                clickedCell.innerHTML = withRate(clicked, sent);
            }
            if (unsubscribeCell) {
                unsubscribeCell.innerHTML = withRate(unsubscribed, sent);
            }
        });
    }

    function refresh(from, to) {
        fetch(endpoint + '?from=' + from + '&to=' + to)
            .then(function (response) {
                if (! response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            })
            .then(function (payload) {
                renderChart(payload);
                fillTotals(payload);
            })
            .catch(function (error) {
                console.error('Error loading chart data', error);
                alert('Error loading chart data: ' + error.message);
            });
    }

    if (rangeInput && window.flatpickr) {
        flatpickr(rangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: [start, end],
            onChange: function (selectedDates) {
                if (selectedDates.length === 2) {
                    refresh(fmt(selectedDates[0]), fmt(selectedDates[1]));
                }
            },
        });
    }

    refresh(fmt(start), fmt(end));
});
</script>
