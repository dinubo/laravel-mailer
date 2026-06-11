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
            <a href="{{ route('mailer.newsletters.edit', $newsletter) . '?delete' }}" class="btn btn-danger text-white">
                Delete
            </a>

            <a href="{{ route('mailer.newsletters.edit', $newsletter) }}" class="btn btn-warning text-white">
                Edit
            </a>

            <a href="{{ route('mailer.newsletters.create') . '?from=' . $newsletter->id }}" class="btn btn-outline-secondary">
                Copy
            </a>

            <form action="{{ route('mailer.newsletters.send', $newsletter) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success text-white">Send</button>
            </form>

            <a href="{{ route('mailer.newsletters.index') }}" class="btn btn-info text-white">
                Back
            </a>
        </h2>

        <div>
            <strong>Details #{{ $newsletter->id }}:</strong> {{ $newsletter->subject }}<br />
            <strong>Segment:</strong> {{ $newsletter->segment ?? '-' }}<br />
            <strong>Event:</strong> {{ $newsletter->event ?? '-' }}<br />
            <strong>Action:</strong> {{ $newsletter->action ?? '-' }}<br />
            <strong>Category:</strong> {{ $newsletter->category ?? '-' }}<br />
            <strong>Scheduled:</strong> {{ $newsletter->after }}<br />
            <strong>Daily Rate:</strong> {{ $newsletter->daily_rate }}<br />
            <strong>Status:</strong> {{ $newsletter->is_active ? 'Active' : 'Inactive' }}
        </div>

        <div class="card-body">
            <div class="mailer-preview" data-mode="mobile">
                <div class="mailer-preview__toolbar">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Preview device">
                        <button type="button" class="btn btn-outline-secondary active" data-mode="mobile">Mobile</button>
                        <button type="button" class="btn btn-outline-secondary" data-mode="tablet">Tablet</button>
                        <button type="button" class="btn btn-outline-secondary" data-mode="desktop">Desktop</button>
                    </div>
                    <span class="mailer-preview__dims" aria-live="polite"></span>
                </div>
                <div class="mailer-preview__stage">
                    <div class="mailer-preview__screen">
                        <iframe class="mailer-preview__frame"
                                src="{{ route('mailer.newsletters.preview', $newsletter) }}"
                                title="Email preview"></iframe>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
@include('mailer::newsletters._statistics', ['endpoint' => route('mailer.newsletters.statistics.show', $newsletter)])

<script>
(function () {
    var preview = document.querySelector('.mailer-preview');
    if (!preview) return;
    var frame   = preview.querySelector('.mailer-preview__frame');
    var screen  = preview.querySelector('.mailer-preview__screen');
    var dims    = preview.querySelector('.mailer-preview__dims');
    var buttons = preview.querySelectorAll('[data-mode][type="button"]');

    function updateDims() {
        if (frame && dims) dims.textContent = Math.round(frame.getBoundingClientRect().width) + ' px';
    }
    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            preview.setAttribute('data-mode', btn.getAttribute('data-mode'));
            buttons.forEach(function (b) { b.classList.toggle('active', b === btn); });
        });
    });
    if (screen) screen.addEventListener('transitionend', function (e) {
        if (e.propertyName === 'width') updateDims();
    });
    if (frame) frame.addEventListener('load', updateDims);
    window.addEventListener('resize', updateDims);
    updateDims();
})();
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<style>
.mailer-preview { margin-top: 15px; }

.mailer-preview__toolbar {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 8px 12px; background: #f8f9fa;
    border: 1px solid #e0e0e0; border-radius: 8px 8px 0 0;
}
.mailer-preview__dims { font-size: .8rem; color: #6c757d; font-variant-numeric: tabular-nums; }

.mailer-preview__stage {
    display: flex; justify-content: center; padding: 24px;
    background: #f1f3f5; border: 1px solid #e0e0e0; border-top: 0;
    border-radius: 0 0 8px 8px; overflow: auto;
}
.mailer-preview__screen {
    width: 100%; height: 760px; background: #fff;
    border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.12);
    overflow: hidden; transition: width .3s ease, height .3s ease, max-width .3s ease;
}
.mailer-preview__frame { width: 100%; height: 100%; border: 0; display: block; }

.mailer-preview[data-mode="mobile"]  .mailer-preview__screen { width: 375px;  height: 667px;  }
.mailer-preview[data-mode="tablet"]  .mailer-preview__screen { width: 768px;  height: 1024px; }
.mailer-preview[data-mode="desktop"] .mailer-preview__screen { width: 100%; max-width: 1280px; height: 760px; }
</style>
@endpush
