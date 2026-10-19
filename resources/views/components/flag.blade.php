@props(['code'])

<span {{ $attributes->class(['fi fi-' . strtolower($code), 'rounded-sm shadow-sm']) }} title="{{ $code }}"></span>
