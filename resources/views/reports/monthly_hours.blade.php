<table>
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
            <th>LUN</th>
            <th>MAR</th>
            <th>MER</th>
            <th>GIO</th>
            <th>VEN</th>
            <th>SAB</th>
            <th>DOM</th>
            <th>TOTALE</th>
            <th>ORE CONTRATTO</th>
            <th>STRAORDINARI</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $u)
            <tr>
                <td>{{ $u['cliente_cod'] }}</td>
                <td>{{ $u['site_cod'] }}</td>
                <td>{{ $u['cliente_nome'] }}</td>
                <td>{{ $u['cantiere'] }}</td>
                <td>{{ $u['matricola'] }}</td>
                <td>{{ $u['utente'] }}</td>
                <td>{{ $u['hired_at'] }}</td>
                <td>{{ $u['end_at'] }}</td>
                <td>{{ $u['mon'] }}</td>
                <td>{{ $u['tue'] }}</td>
                <td>{{ $u['wed'] }}</td>
                <td>{{ $u['thu'] }}</td>
                <td>{{ $u['fri'] }}</td>
                <td>{{ $u['sat'] }}</td>
                <td>{{ $u['sun'] }}</td>
                <td>{{ $u['total_hours'] }}</td>
                <td>{{ $u['contract'] }}</td>
                <td>{{ $u['overtime'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
