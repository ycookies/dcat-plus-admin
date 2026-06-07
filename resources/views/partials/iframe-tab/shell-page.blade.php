<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="chrome=1,IE=edge">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <title>@if(! empty($header)){{ $header }} | @endif {{ Dcat\Admin\Admin::title() }}</title>

    @if(! config('admin.disable_no_referrer_meta'))
        <meta name="referrer" content="no-referrer"/>
    @endif

    @if(! empty($favicon = Dcat\Admin\Admin::favicon()))
        <link rel="shortcut icon" href="{{ $favicon }}">
    @endif

    {!! admin_section(Dcat\Admin\Admin::SECTION['HEAD']) !!}

    {!! Dcat\Admin\Admin::asset()->headerJsToHtml() !!}

    {!! Dcat\Admin\Admin::asset()->cssToHtml() !!}
    <link rel="stylesheet" href="{{ asset('/vendor/dcat-admin/dcat/plugins/admin-iframe-tab/css/admin-iframe-tab.css') }}?v={{ config('admin.iframe_tab.asset_version', '1.0.0') }}">
</head>

@extends('admin::partials.iframe-tab.shell-container')
