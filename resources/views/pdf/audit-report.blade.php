<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="utf-8">
    <title>Reunion Digital Standard — {{ $assessment->company->name }}</title>
    <style>
        @page {
            margin: 28px 36px 48px 36px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #27272a;
        }

        h1, h2, h3 {
            margin: 0;
            font-weight: bold;
        }

        .header {
            border-bottom: 2px solid #18181b;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        .brand {
            font-size: 10px;
            letter-spacing: 1px;
            color: #71717a;
            text-transform: uppercase;
        }

        .company-name {
            font-size: 20px;
        }

        .meta {
            font-size: 10px;
            color: #71717a;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #52525b;
            border-bottom: 1px solid #d4d4d8;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }

        .score-box {
            width: 100%;
        }

        .score-number {
            font-size: 40px;
            font-weight: bold;
        }

        .status-label {
            font-size: 15px;
            font-weight: bold;
        }

        .bar-track {
            background: #e4e4e7;
            border-radius: 4px;
            height: 10px;
            width: 100%;
        }

        .bar-fill {
            background: #2563eb;
            border-radius: 4px;
            height: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .area-table td {
            padding: 6px 4px;
            vertical-align: middle;
        }

        .area-name {
            width: 32%;
            font-weight: bold;
        }

        .area-score {
            width: 10%;
            text-align: right;
            font-weight: bold;
        }

        .area-bar {
            width: 58%;
        }

        .findings-table {
            width: 100%;
        }

        .findings-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px 0 0;
        }

        ul.findings {
            margin: 0;
            padding-left: 14px;
        }

        ul.findings li {
            margin-bottom: 4px;
        }

        .plan-item {
            border-bottom: 1px solid #e4e4e7;
            padding: 6px 0;
        }

        .plan-item .text {
            font-weight: bold;
        }

        .plan-item .meta {
            margin-top: 2px;
        }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 9px;
            color: #fff;
            background: #52525b;
        }

        .badge-kritican { background: #dc2626; }
        .badge-vazan { background: #d97706; }

        .empty {
            color: #a1a1aa;
            font-style: italic;
        }

        footer {
            position: fixed;
            bottom: -36px;
            left: 0;
            right: 0;
            height: 30px;
            font-size: 9px;
            color: #a1a1aa;
            text-align: center;
            border-top: 1px solid #e4e4e7;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <footer>
        Reunion Digital Standard — Izvještaj generisan {{ now()->translatedFormat('d.m.Y. H:i') }}
    </footer>

    <div class="header">
        <div class="brand">Reunion Digital Standard</div>
        <h1 class="company-name">{{ $assessment->company->name }}</h1>
        <div class="meta">
            @if ($assessment->status === 'completed')
                Audit završen {{ $assessment->completed_at?->translatedFormat('d.m.Y.') }}
            @else
                Preliminarni rezultati — audit u toku od {{ $assessment->started_at?->translatedFormat('d.m.Y.') ?? $assessment->created_at->translatedFormat('d.m.Y.') }}
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Ukupni digitalni score</div>

        <table>
            <tr>
                <td style="width: 120px;">
                    <div class="score-number">{{ number_format($overallScore, 0) }}<span style="font-size:16px;">/100</span></div>
                </td>
                <td>
                    <div class="status-label" style="color: {{ $status['color'] }};">{{ Str::upper($status['label']) }}</div>
                    <div>{{ $status['description'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Rezultati po oblastima</div>

        <table class="area-table">
            @foreach ($areaScores as $entry)
                <tr>
                    <td class="area-name">{{ $entry['area']->name }}</td>
                    <td class="area-score">{{ number_format($entry['score'], 0) }}</td>
                    <td class="area-bar">
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ max(0, min(100, $entry['score'])) }}%; background: {{ $entry['color'] }};"></div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <div class="section-title">Top nalazi</div>

        <table class="findings-table">
            <tr>
                <td>
                    <h3>Glavne snage</h3>
                    @if ($strengths->isEmpty())
                        <p class="empty">Nema još dovoljno odgovora.</p>
                    @else
                        <ul class="findings">
                            @foreach ($strengths as $row)
                                <li>{{ $row['criterion']->text }}</li>
                            @endforeach
                        </ul>
                    @endif
                </td>
                <td>
                    <h3>Prioriteti za unapređenje</h3>
                    @if ($priorities->isEmpty())
                        <p class="empty">Nema još dovoljno odgovora.</p>
                    @else
                        <ul class="findings">
                            @foreach ($priorities as $row)
                                <li>{{ $row['criterion']->text }}</li>
                            @endforeach
                        </ul>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Akcioni plan</div>

        @foreach ($actionPlan as $horizon => $items)
            @continue($items->isEmpty())

            <h3 style="margin-bottom: 6px;">{{ $horizon }}</h3>

            @foreach ($items as $item)
                <div class="plan-item">
                    <div class="text">{{ $item['criterion']->text }}</div>
                    <div class="meta">
                        <span class="badge badge-{{ $item['criterion']->priority }}">
                            {{ ['kritican' => 'Kritičan', 'vazan' => 'Važan'][$item['criterion']->priority] ?? $item['criterion']->priority }}
                        </span>
                        {{ $item['area']->name }} / {{ $item['workbook']->name }}
                        — izgubljeno {{ number_format($item['gap'], 2) }} od {{ number_format($item['max'], 2) }} boda
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
</body>
</html>
