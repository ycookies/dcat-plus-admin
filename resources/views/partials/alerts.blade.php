@php
    $alertValueToString = function ($value, $glue = '<br>') use (&$alertValueToString) {
        if (is_array($value)) {
            $parts = [];

            foreach ($value as $item) {
                $item = $alertValueToString($item, $glue);

                if ($item !== '') {
                    $parts[] = $item;
                }
            }

            return implode($glue, $parts);
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return '';
    };

    $alertTitle = function ($alert) use ($alertValueToString) {
        if ($alert instanceof \Illuminate\Support\MessageBag) {
            return $alertValueToString($alert->get('title'), ' ');
        }

        return $alertValueToString(\Illuminate\Support\Arr::get((array) $alert, 'title', ''), ' ');
    };

    $alertMessage = function ($alert) use ($alertValueToString) {
        if ($alert instanceof \Illuminate\Support\MessageBag) {
            return $alertValueToString($alert->get('message'));
        }

        return $alertValueToString(\Illuminate\Support\Arr::get((array) $alert, 'message', ''));
    };
@endphp

@if($error = session()->get('error'))
    <div class="alert alert-danger alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h4><i class="icon fa fa-ban"></i> &nbsp;{{ $alertTitle($error) }}</h4>
        <p>{!! $alertMessage($error) !!}</p>
    </div>
@elseif ($errors = session()->get('errors'))
    @if ($errors->hasBag('error'))
      <div class="alert alert-danger alert-dismissable">

        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        @foreach($errors->getBag("error")->toArray() as $message)
            <p>{!!  \Illuminate\Support\Arr::get($message, 0) !!}</p>
        @endforeach
      </div>
    @endif
@endif

@if($success = session()->get('success'))
    <div class="alert alert-success alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h4><i class="icon fa fa-check"></i> &nbsp;{{ $alertTitle($success) }}</h4>
        <p>{!! $alertMessage($success) !!}</p>
    </div>
@endif

@if($info = session()->get('info'))
    <div class="alert alert-info alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h4><i class="icon fa fa-info"></i> &nbsp;{{ $alertTitle($info) }}</h4>
        <p>{!! $alertMessage($info) !!}</p>
    </div>
@endif

@if($warning = session()->get('warning'))
    <div class="alert alert-warning alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h4><i class="icon fa fa-warning"></i> &nbsp;{{ $alertTitle($warning) }}</h4>
        <p>{!! $alertMessage($warning) !!}</p>
    </div>
@endif