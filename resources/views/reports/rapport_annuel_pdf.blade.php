<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport annuel {{ $rapport['annee'] ?? '' }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        h1, h2, h3 { margin: 0 0 10px 0; }
        .section { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Rapport annuel {{ $rapport['annee'] ?? '' }}</h1>

    <div class="section">
        <h2>Statistiques globales</h2>
        @php($stats = $rapport['statistiques'] ?? [])
        <table>
            <tbody>
                <tr>
                    <th>Documents générés</th>
                    <td>{{ $stats['documents_generes'] ?? 0 }}</td>
                </tr>
                <tr>
                    <th>Absences déclarées</th>
                    <td>{{ $stats['absences_declarees'] ?? 0 }}</td>
                </tr>
                <tr>
                    <th>Utilisateurs inscrits</th>
                    <td>{{ $stats['utilisateurs_inscrits'] ?? 0 }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Documents générés (extrait)</h2>
        @php($docs = $rapport['documents_generes'] ?? [])
        @if(count($docs))
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Étudiant</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($docs as $doc)
                        <tr>
                            <td>{{ $doc->nom ?? 'Document' }}</td>
                            <td>{{ $doc->type ?? ($doc->modeleDocument->type ?? '-') }}</td>
                            <td>{{ optional(optional($doc->etudiant)->utilisateur)->nom }}</td>
                            <td>{{ optional($doc->date_generation)->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Aucun document pour cette année.</p>
        @endif
    </div>

    <div class="section">
        <h2>Absences traitées (extrait)</h2>
        @php($absences = $rapport['absences_traitees'] ?? [])
        @if(count($absences))
            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Statut</th>
                        <th>Date déclaration</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($absences as $a)
                        <tr>
                            <td>{{ optional(optional($a->etudiant)->utilisateur)->nom }}</td>
                            <td>{{ $a->statut }}</td>
                            <td>{{ optional($a->date_declaration)->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Aucune absence pour cette année.</p>
        @endif
    </div>
</body>
</html>
