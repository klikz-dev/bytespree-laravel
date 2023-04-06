@extends('layouts.email')

@section('content')
Hello,
<br />
<br />
<p>The MySQL Reconcile ran for a total of {{ count($data) }} tables in the {{  $this->environment->getTeamName() }} team.</p>
<p>Summary:</p>
<table style="border-collapse: collapse;">
        <thead>
            <tr>
                <th style="width: 20%; border: 1px solid black;">Table</th>
                <th style="width: 20%; border: 1px solid black;">Total MySQL Count</th>
                <th style="width: 20%; border: 1px solid black;">Total Shadow Database Count</th>
                <th style="width: 20%; border: 1px solid black;">Difference</th>
                <th style="width: 20%; border: 1px solid black;">Ran On</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $tabledata)
                @php
                    $datetime = explode(' ', $tabledata["date_started"]);
                @endphp
                <tr>
                    <td style="width: 20%; border: 1px solid black;">{{ $tabledata["table_name"] }}</td>
                    <td style="width: 20%; text-align: right; border: 1px solid black;">{{ $tabledata["api_count"] }}</td>
                    <td style="width: 20%; text-align: right; border: 1px solid black;">{{ $tabledata["shadow_count"] }}</td>
                    @if($tabledata["difference"] === 0)
                        <td style="color: red; width: 20%; text-align: right; border: 1px solid black;">{{ $tabledata["difference"] }}</td>
                    @else
                        <td  style="width: 20%; text-align: right; border: 1px solid black;">{{ $tabledata["difference"] }}</td>
                    @endif
                    <td style="width: 20%; text-align: right; border: 1px solid black;">{{ $datetime[0] }}<br/> {{ date('h:i:s a', strtotime($datetime[1])) }}</td>
                </tr>
            @endforeach
        </tbody>
</table>
<p>In Depth Data:</p>
@foreach ($data as $tabledata)
    <br />
    <table style="border-collapse: collapse;">
        <thead>
            <tr>
                <th colspan="4"style="border: 1px solid black;">Table: {{ $tabledata["table_name"] }} Ran On: {{ $tabledata["date_started"] }}</th>
            </tr>
            <tr>
                <th style="width: 25%; border: 1px solid black;">Date</th>
                <th style="width: 25%; border: 1px solid black;">MySQL Count</th>
                <th style="width: 25%; border: 1px solid black;">Shadow Database Count</th>
                <th style="width: 25%; border: 1px solid black;">Difference</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tabledata["day_data"] as $key => $daydata)
                <tr>
                    <td style="width: 25%; border: 1px solid black;">{{ $daydata["date"] }}</td>
                    <td style="width: 25%; text-align: right; border: 1px solid black;">{{ $daydata["api_count"] }}</td>
                    <td style="width: 25%; text-align: right; border: 1px solid black;">{{ $daydata["shadow_count"] }}</td>
                    @if($daydata["difference"] === 0)
                        <td style="color: red; width: 25%; text-align: right; border: 1px solid black;">{{ $daydata["difference"] }}</td>
                    @else
                        <td  style="width: 25%; text-align: right; border: 1px solid black;">{{ $daydata["difference"] }}</td>
                    @endif
                </tr>
            @endforeach
            <tr>
                <td style="width: 25%; border: 1px solid black;">Totals</td>
                <td style="width: 25%; text-align: right; border: 1px solid black;">{{ $tabledata["api_count"] }}</td>
                <td style="width: 25%; text-align: right; border: 1px solid black;">{{ $tabledata["shadow_count"] }}</td>
                @if($tabledata["difference"] > 0)
                    <td style="color: red; width: 25%; text-align: right; border: 1px solid black;">{{ $tabledata["difference"] }}</td>
                @else
                    <td style="width: 25%; text-align: right; border: 1px solid black;">{{ $tabledata["difference"] }}</td>
                @endif
            </tr>
        </tbody>
    </table>
@endforeach
@endsection
