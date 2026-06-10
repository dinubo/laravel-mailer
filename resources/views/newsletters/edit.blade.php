@extends('mailer::layouts.app')

@section('content')
<div class="container">

    <div class="section">

        <div class="col-md-12">

            @if ($for_deletion)
                <div class="card">

                    <div class="card-header card-header-primary">
                        <strong>Delete #{{ $newsletter->id }}:</strong> {{$newsletter->subject}}
                    </div>

                    <div class="card-body">
                        <p>Are you sure you want to delete Newsletter "{{ $newsletter->subject }}" with id #{{ $newsletter->id }} ?</p>

                        <form method="POST" action="{{ route('mailer.newsletters.destroy', $newsletter) }}">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-danger">
                                <i class="material-icons">delete</i>
                                Delete Newsletter
                            </button>
                            <a href="{{ route('mailer.newsletters.show', $newsletter) }}" class="btn btn-default">
                                Cancel
                            </a>
                        </form>
                    </div>
                </div>
            @else
                <div class="card">

                    <div class="card-header card-header-primary">
                        <strong>Edit #{{ $newsletter->id }}:</strong> {{$newsletter->subject}}
                    </div>

                    <div class="card-body">

                        <div class="text-right">
                            <a href="{{ route('mailer.newsletters.show', $newsletter) }}" class="btn btn-info text-white">Back</a><br /><br />
                        </div>

                        <form class="form-horizontal" method="POST" action="{{ route('mailer.newsletters.update', $newsletter) }}">
                            @method('put')
                            @include('mailer::newsletters._form', ['buttonText' => 'Update Newsletter'])
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
