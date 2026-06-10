@extends('mailer::layouts.app')

@section('content')
<div class="container">

    <div class="section">

        <div class="col-md-12">

            <div class="card">

                <div class="card-header card-header-primary">
                    Create Newsletter
                </div>

                <div class="card-body">

                    <div class="text-right">
                        <a href="{{ $newsletter->exists ? route('mailer.newsletters.show', $newsletter) : route('mailer.newsletters.index') }}" class="btn btn-info text-white">Back</a><br /><br />
                    </div>

                    <form class="form-horizontal" method="POST" action="{{ route('mailer.newsletters.store') }}">
                        @include('mailer::newsletters._form', ['buttonText' => 'Create Newsletter'])
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
