@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@php
    $slot = 'Logo';
@endphp
@if (trim($slot) === 'Logo')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
