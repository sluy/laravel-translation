@extends('layouts.app')

@section('pageTitle')
    {!! __("laravel-translation::words.import_translations") !!}
    ({!! $driver !!})
@endsection
@section('content')
<div class="container">

    <div class="row">
        <div class="col">
            <h1>
                {!! __("laravel-translation::drivers.{$driver}.title") !!}
            </h1>
            <h3 class="text-muted mb-4">
                {!! __("laravel-translation::words.import_translations") !!}
            </h3>
        </div>
    </div>

    <div class="row">
        <div class="col-3 d-none d-md-block">
            <div class="list-group">
                @foreach ($all as $name => $cfg)
                    @if ($name === $driver)
                        <li class="list-group-item list-group-item-primary">{!! __("laravel-translation::drivers.{$name}.title")!!}</li>
                    @else
                        <a href="{!! route('laravel-translation.driver.edit', ['driver' => $name]) !!}" class="list-group-item list-group-item-action">{!! __("laravel-translation::drivers.{$name}.title")!!}</a>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="col-xs-12 col-md-9">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('laravel-translation.driver.update', ['driver' => $driver])}}" method="POST" enctype="multipart/form-data">

                        @method('PUT')
                        @csrf
                        
                        <div class="form-group">
                            <label for="from">{!! __('laravel-translation::words.import_from') !!}</label>
                            <select name="from" class="form-control" id="type-control" onchange="switch_from(this.value)">
                                <option value="">{!! __('laravel-translation::words.select_an_option')  !!}
                                <option value="upload"{!! old('from') === 'upload' ? ' selected' : '' !!}>{!! __('laravel-translation::words.upload') !!}</option>
                                <option value="filesystem"{!! old('from') === 'filesystem' ? ' selected' : '' !!}>{!! __('laravel-translation::words.another_driver') !!}</option>
                            </select>
                        </div>
                        <!-- UPLOAD -->
                        <div class="d-none driver-from-group" id="upload-from-group">
                            <div class="form-group">
                                <label for="upload-source">{!! __('laravel-translation::words.source')  !!}</label>
                                <select id="upload-source" name="upload-source" class="form-control">
                                    <option value="">{!! __('laravel-translation::words.select_an_option')  !!}
                                    @foreach($fileDrivers as $name => $cfg)
                                        <option value="{!! $name !!}"{{old('upload-source') === $name ? ' selected' : ''}}>
                                            {!! __("laravel-translation::drivers.{$name}.title")  !!}
                                        </option>
                                    @endforeach
                                </select>
                                @if(old('from') === 'upload' && $errors->has('upload-source'))
                                <p class="text-danger">{{$errors->first('upload-source')}}</p>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="upload-file">{!! __('laravel-translation::words.file_to_upload')  !!}
                                <input id="upload-file" name="upload-file" type="file" class="form-control" accept=".zip">
                                @if(old('from') === 'upload' && $errors->has('upload-file'))
                                <p class="text-danger">{{$errors->first('upload-file')}}</p>
                                @endif
                            </div>
                        </div>
                        <!-- FILESYSTEM -->
                        <div class="d-none driver-from-group" id="filesystem-from-group">
                            
                            <div class="form-group">
                                <label for="filesystem-source">{!! __('laravel-translation::words.source')  !!}</label>
                                <select id="filesystem-source" name="filesystem-source" class="form-control" onchange="switch_locales(this.value)">
                                    <option value="">{!! __('laravel-translation::words.select_an_option')  !!}
                                    @foreach($importable as $name => $locales)
                                        <option value="{!! $name !!}"{{old('source') === $name ? ' selected' : ''}}>
                                            {!! __("laravel-translation::drivers.{$name}.title")  !!}
                                            ({{implode(', ', array_keys($locales))}})
                                        </option>
                                    @endforeach
                                </select>
                                @if(old('from') === 'filesystem' && $errors->has('filesystem-source'))
                                <p class="text-danger">{{$errors->first('filesystem-source')}}</p>
                                @endif
                            </div>
                            <div class="form-group">
                                <label>{!! __('laravel-translation::words.locales')  !!}</label>
                            </div>
                            @foreach ($importable as $name => $locales)
                                <div class="form-group d-none driver-locale-group" id="{{$name}}-driver-locales">
                                    @foreach($locales as $locale => $count)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="filesystem-locales[]" value="{!! $locale !!}">
                                            <label class="form-check-label" for="inlineCheckbox1">{!!$locale !!} ({!! $count !!})</label>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                            @if(old('from') === 'filesystem' && $errors->has('locales'))
                                <div class="form-group">
                                    <p class="text-danger">{{$errors->first('locales')}}</p>
                                </div>
                            @endif
                        </div>
                        <div class="text-right">
                            <a class="btn btn-default text-uppercase" href="{{route('laravel-translation.driver.index')}}">{!! __('laravel-translation::words.cancel')  !!}</a>
                            <button class="btn btn-primary text-uppercase" type="submit">{!! __('laravel-translation::words.import')  !!}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function switch_locales(driver) {
        document.querySelectorAll(".driver-locale-group input[type=checkbox]").forEach(function(el) {
            el.checked = false;
        });
        document.querySelectorAll('.driver-locale-group').forEach(function (el) {
            if (typeof driver === 'string' && driver.length > 0 && el.id === driver + '-driver-locales') {
                el.classList.remove('d-none');
                el.classList.add('d-block');
            } else {
                el.classList.remove('d-block');
                el.classList.add('d-none');
            }
        });
    }
    function switch_from(type) {
        document.querySelectorAll('.driver-from-group').forEach(function (el) {
            var selectedId = type + '-from-group';
            if (el.id === selectedId) {
                el.classList.add('d-block');
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
                el.classList.remove('d-block');
            }
        });
    }
    @if(old('from') === 'filesystem')
        switch_from('filesystem');
        @if (!empty(old('filesystem-source')))
            switch_locales(old('filesystem-source'));
        @endif
    @elseif(old('from') === 'upload')
        switch_from('upload');
    @endif
</script>
@endsection
