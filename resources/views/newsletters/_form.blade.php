@csrf

<div class="form-group">
    <div class="col-sm-offset-1 col-sm-10">
        @include('mailer::errors._errors')
    </div>
</div>

<div class="form-row">

    <div class="form-group col-md-2">
        <label for="is_active" class="control-label">Status</label>
        <select class="custom-select" id="is_active" name="is_active">
            @php $isActive = count($errors) ? old('is_active') : $newsletter->is_active; @endphp
            <option value="0" {{ ! $isActive ? 'selected=selected' : '' }}>Inactive</option>
            <option value="1" {{ $isActive ? 'selected=selected' : '' }}>Active</option>
        </select>
    </div>

    <div class="form-group col-md-4">
        <label for="segment" class="control-label">Segment</label>
        <select class="custom-select" id="segment" name="segment">
            <option value="">=== None ===</option>
            @foreach ($segments as $segment)
                <option
                    value="{{ $segment['value'] }}"
                    {{ (count($errors) ? old('segment') : $newsletter->segment) == $segment['value'] ? 'selected=selected' : '' }}
                >{{ $segment['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-md-3">
        <label for="event" class="control-label">Event</label>
        <select class="custom-select" id="event" name="event">
            <option value="">=== None ===</option>
            @foreach ($events as $event)
                <option
                    value="{{ $event['value'] }}"
                    {{ (count($errors) ? old('event') : $newsletter->event) == $event['value'] ? 'selected=selected' : '' }}
                >{{ $event['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-md-3">
        <label for="action" class="control-label">Action</label>
        <select class="custom-select" id="action" name="action">
            <option value="">=== None ===</option>
            @foreach ($actions as $action)
                <option
                    value="{{ $action['value'] }}"
                    {{ (count($errors) ? old('action') : $newsletter->action) == $action['value'] ? 'selected=selected' : '' }}
                >{{ $action['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-md-4">
        <label for="category" class="control-label">Category</label>
        <input type="text" class="form-control" id="category" name="category" value="{{ count($errors) ? old('category') : $newsletter->category }}">
    </div>

    <div class="form-group col-md-4">
        <label for="after" class="control-label">Send after</label>
        <input type="text" class="form-control" id="after" name="after" placeholder="7 days" value="{{ count($errors) ? old('after') : $newsletter->after }}">
    </div>

    <div class="form-group col-md-4">
        <label for="daily_rate" class="control-label">Daily Rate (Compaigns)</label>
        <input type="number" class="form-control" id="daily_rate" name="daily_rate" value="{{ count($errors) ? old('daily_rate') : $newsletter->daily_rate }}">
    </div>
</div>

<div class="form-group">
    <label for="subject" class="control-label">Subject</label>
    <input type="text" class="form-control" id="subject" name="subject" value="{{ count($errors) ? old('subject') : $newsletter->subject }}">
</div>

<div class="form-group">
    <label for="body" class="control-label">Body</label>
    <textarea rows="20" class="form-control" style="height: initial" id="body" name="body">{{ count($errors) ? old('body') : $newsletter->body }}</textarea>
    <div class="placeholders-code ml-1 mb-3">
        <div>
            Link: <code>[Link Text](https://www.example.com/)</code><br />
            {{-- Image: <code>![Alt Text](https://www.example.com/image_file.png)</code><br /> --}}
            {{-- Button: <code>?[Button Text](https://www.example.com/)</code><br /> --}}
        </div>

        <div>
            Global:<br />
            @foreach($placeholders as $placeholder)
                => {{ $placeholder['name'] }}: <code>{{ $placeholder['key'] }}</code><br />
            @endforeach
        </div>

        @foreach($events as $event)
            @if($event['placeholders']->count())
            <div class="mt-2">
                [event] {{ $event['name'] }}:<br />
                @foreach($event['placeholders'] as $placeholder)
                => {{ $placeholder['name'] }}: <code>{{ $placeholder['key'] }}</code><br />
                @endforeach
            </div>
            @endif
        @endforeach

        @foreach($actions as $action)
            @if($action['placeholders']->count())
            <div class="mt-2">
                [action] {{ $action['name'] }}:<br />
                @foreach($action['placeholders'] as $placeholder)
                => {{ $placeholder['name'] }}: <code>{{ $placeholder['key'] }}</code><br />
                @endforeach
            </div>
            @endif
        @endforeach
    </div>
</div>

<div class="form-row justify-content-center form-group text-center">
    <div class="col-sm-8">
        <button type="submit" class="btn btn-primary btn-lg btn-block">{{$buttonText}}</button>
    </div>
</div>

@push('styles')
<style>
    .placeholders-code {
        color: #808080;
        font-size: .86em;
    }

    .placeholders-code code {
        color: inherit;
        background-color: inherit;
        font-weight: inherit;
    }
</style>
@endpush
