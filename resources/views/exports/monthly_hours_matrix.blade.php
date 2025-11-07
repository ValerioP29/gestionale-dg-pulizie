@php
    use Carbon\Carbon;
    $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
@endphp

<table border="1" cellspacing="0" cellpadding="4">
    <thead>
        <tr>
            <th>CLIENTE COD</th>
            <th>SITE COD</th>
            <th>CLIENTE</th>
            <th>CANTIERE</th>
            <th>MATRICOLA</th>
            <th>COGNOME + NOME</th>
            <th>ASSUNZIONE</th>
            <th>SCADENZA</th>

            {{-- Giorni mese dinamici --}}
            @for ($d = 1; $d <= $daysInMonth; $d++)
                <th>{{ $d }}</th>
            @endfor

            <th>TOTALE</th>
            <th>STRAORDINARI</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($rows as $r)
            <tr>
                <td>{{ $r['cliente_cod'] }}</td>
                <td>{{ $r['site_cod'] }}</td>
                <td>{{ $r['cliente_nome'] }}</td>
                <td>{{ $r['cantiere'] }}</td>
                <td>{{ $r['matricola'] }}</td>
                <td>{{ $r['utente'] }}</td>
                <td>{{ $r['hired_at'] }}</td>
                <td>{{ $r['end_at'] }}</td>

                {{-- ore giorno per giorno --}}
                @foreach ($r['giorni'] as $ore)
                    <td>{{ $ore }}</td>
                @endforeach

                <td><strong>{{ $r['total_hours'] }}</strong></td>
                <td><strong>{{ $r['overtime'] }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>
