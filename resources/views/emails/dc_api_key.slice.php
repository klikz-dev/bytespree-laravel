@extends('layouts.email')

@section('content')
Hello,
<br />
<p>An API key has been assigned for your organization.  This email address was specified as the recipient of the key.</p>

<p>The API key is:</p>

<p>{{ $api_key }}</p>

@endsection