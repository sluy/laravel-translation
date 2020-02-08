@extends('laravel-translation::template')
@section('pageTitle')
    {!! __("laravel-translation::words.available_drivers") !!}
@endsection
@section('content')
<div class="container">

    <div class="row">
        <div class="col">
             <h1 class="mb-4">
                {!! __("laravel-translation::words.available_drivers") !!} ({{count($drivers)}})
            </h1>
        </div>
    </div>
    @if (is_array(session('status')))
        @if(session('status')['count'] === 0)
            <div class="alert alert-info">
                {!! __('laravel-translation::words.zero_changes') !!}
            </div>
        @else
            <div class="alert alert-success">
                @if(session('status')['type'] === 'save')
                    {!! __('laravel-translation::words.translations_saved', ['count' => session('status')['count']]) !!}
                @else
                    {!! __('laravel-translation::words.translations_destroyed', ['count' => session('status')['count']]) !!}

                @endif
            </div>
        @endif
    @endif

    <div class="row">
        @forelse($drivers as $name => $driver)
        <div class="col col-md-6 ">
            <div class="card mb-4">
                <div class="card-body">
                    <a href="#">
                    <h3 class="text-uppercase">
                        {!! __("laravel-translation::drivers.{$name}.title") !!}
                    </h3>
                    </a>
                    <p>
                        {!! __("laravel-translation::drivers.{$name}.description") !!}
                    </p>

                    <h5>{!! __("laravel-translation::words.location") !!}</h5>
                    <p>
                        <code>{{$driver['location']}}</code>
                    </p>
                    
                    <h5>
                        {!! __("laravel-translation::words.locales") !!}
                    </h5>
                    <p>
                    @forelse($driver['locales'] as $locale => $count)
                        <span class="badge badge-dark text-uppercase">
                            {{$locale }} ({{$count}})
                            <a href="#clear-locale"
                                class="text-danger"
                                onclick="event.preventDefault();document.getElementById('clear-{{$name}}-{{$locale}}').submit()">
                                x
                            </a>
                        </span>
                    @empty
                        <span class="text-muted">{!! __('laravel-translation::words.no_translations') !!}
                    @endforelse
                    </p>
                    @foreach($driver['locales'] as $locale => $count)
                        <form id="clear-{{$name}}-{{$locale}}" action="{{ route('laravel-translation.driver.destroy',['driver'=>$name, 'locale' => $locale]) }}" method="POST" style="display: none !important;">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach


                </div>
                <div class="card-footer text-right bg-white">
                    <a href="#clear"
                        class="btn btn-danger text-uppercase btn-sm"
                        onclick="event.preventDefault();document.getElementById('clear-all-{{$name}}').submit()">
                        {!! __('laravel-translation::words.clear') !!}
                    </a>
                    <form id="clear-all-{{$name}}" action="{{ route('laravel-translation.driver.destroy',['driver'=>$name]) }}" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>

                    <a href="{{route('laravel-translation.driver.edit', ['driver' => $name])}}" class="btn btn-success text-uppercase btn-sm">
                        {!! __('laravel-translation::words.import') !!}
                    </a>
                    @if($driver['file'] === true)
                    <a href="{{route('laravel-translation.driver.download', ['driver' => $name])}}" class="btn btn-primary text-uppercase btn-sm">
                        {!! __('laravel-translation::words.download') !!}
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @empty
            <div class="col">
                <p>
                {!! __("laravel-translation::words.no_drivers_found") !!}
                </p>
                <p>
                {!!__("laravel-translation::words.git_section_help", ['node' => 'configuration', 'section' => __('laravel-translation::words.configuration')])!!}
                </p>
            </div>
        @endforelse
        <br/>
    </div>
</div>

@endsection
