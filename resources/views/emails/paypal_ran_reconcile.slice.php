@extends('layouts.email')

@section('content')
Hello,
<br />
<p>We received a total of <b>{{ $paypal_count }}</b> records from the PayPal API in the {{  $this->environment->getTeamName() }} team. The shadow database now has <b>{{ $shadow_count }}</b> records. The difference between the two is <b>{{ $difference }}</b>.</p>
<br />
<table style="border-collapse: collapse;">
    <thead>
        <tr>
            <th style="width: 20%; border: 1px solid black;">Date</th>
            <th style="width: 20%; border: 1px solid black;">PayPal Amount</th>
            <th style="width: 20%; border: 1px solid black;">PayPal Count</th>
            <th style="width: 20%; border: 1px solid black;">Shadow Database Count</th>
            <th style="width: 20%; border: 1px solid black;">Difference</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($table as $key => $daydata)
            <tr>
                <td style="width: 20%; border: 1px solid black;">{{ $daydata["date"] }}</td>
                <td style="width: 20%; text-align: right; border: 1px solid black;">
                    @php
                        setlocale(LC_MONETARY,"en_US.UTF-8");
                        echo money_format("%n", $daydata["paypal_amount"]);
                    @endphp
                </td>
                <td style="width: 20%; text-align: right; border: 1px solid black;">{{ $daydata["paypal_count"] }}</td>
                <td style="width: 20%; text-align: right; border: 1px solid black;">{{ $daydata["shadow_count"] }}</td>
                @if($daydata["difference"] === 0)
                    <td style="width: 20%; text-align: right; border: 1px solid black;">{{ $daydata["difference"] }}</td>
                @else
                    <td  style="color: red; width: 20%; text-align: right; border: 1px solid black;">{{ $daydata["difference"] }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
