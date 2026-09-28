<!DOCTYPE html>
<html lang="pt-BR"><head>
<meta charset="utf-8"><title>Relatório Exclusive Locker</title>
<style>body{font-family:Arial,sans-serif;color:#111} table{width:100%;border-collapse:collapse;font-size:12px} th,td{border:1px solid #ddd;padding:6px;text-align:left} h1{font-size:20px}</style>
</head><body onload="window.print()">
<h1>Exclusive Locker — Correspondências</h1>
<p>Gerado em {{ now()->format('d/m/Y H:i') }}</p>
<table><thead><tr><th>Entrada</th><th>Morador</th><th>Unidade</th><th>Porta</th><th>Senha</th><th>Status</th></tr></thead>
<tbody>@foreach($rows as $r)
<tr><td>{{ optional($r->data_entrada)->format('d/m/Y H:i') }}</td><td>{{ $r->morador_nome }}</td><td>{{ $r->bloco }} {{ $r->apartamento }}</td><td>{{ $r->porta }}</td><td>{{ $r->senha }}</td><td>{{ $r->status }}</td></tr>
@endforeach</tbody></table>
</body></html>
