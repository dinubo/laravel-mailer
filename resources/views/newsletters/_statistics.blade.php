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

    function fmt(d) {
        var month = ('0' + (d.getMonth() + 1)).slice(-2);
        var day = ('0' + d.getDate()).slice(-2);
        return d.getFullYear() + '-' + month + '-' + day;
    }

    var from = "{{ request('from') }}";
    var to = "{{ request('to') }}";
    var picker;

    function sum(arr) {
        return (arr || []).reduce(function (total, value) { return total + (value || 0); }, 0);
    }

    function withRate(count, sent) {
        var rate = sent > 0 ? (count / sent * 100).toFixed(2) + '%' : '—';
        return count + '<br>(' + rate + ')';
    }

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

    function syncPicker(labels) {
        if (! labels || ! labels.length) {
            return;
        }

        var from = labels[0];
        var to = labels[labels.length - 1];

        if (picker) {
            picker.setDate([from, to], false);
        }

        updateUrl(from, to);
    }

    function refresh(from, to) {
        var params = [];
        if (from) {
            params.push('from=' + from);
        }
        if (to) {
            params.push('to=' + to);
        }

        fetch(endpoint + (params.length ? '?' + params.join('&') : ''))
            .then(function (response) {
                if (! response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            })
            .then(function (payload) {
                renderChart(payload);
                fillTotals(payload);
                syncPicker(payload.labels);
            })
            .catch(function (error) {
                console.error('Error loading chart data', error);
                alert('Error loading chart data: ' + error.message);
            });
    }

    function updateUrl(from, to) {
        try {
            var here = new URL(window.location.href);
            here.searchParams.set('from', from);
            here.searchParams.set('to', to);
            window.history.replaceState({}, '', here);
        } catch (e) {}
    }

    function syncRange(from, to) {
        updateUrl(from, to);

        document.querySelectorAll('a.js-range-link, .js-pagination a').forEach(function (link) {
            try {
                var url = new URL(link.href);
                url.searchParams.set('from', from);
                url.searchParams.set('to', to);
                link.href = url.toString();
            } catch (e) {}
        });
    }

    if (rangeInput && window.flatpickr) {
        picker = flatpickr(rangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            onChange: function (selectedDates) {
                if (selectedDates.length === 2) {
                    var from = fmt(selectedDates[0]);
                    var to = fmt(selectedDates[1]);
                    refresh(from, to);
                    syncRange(from, to);
                }
            },
        });
    }

    refresh(from, to);
});
</script>
