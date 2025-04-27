<?php
class Mock_Lx_Events {
    public function Get_Events() {
        $mockEvents = [
            'E_100000' => [
                'AuftragId' => 'E_100000',
                'AuftragsNr' => 'EVENT_001',
                'AuftragsKennung' => 1,
                'Datum_Erfassung' => '2025-04-28 09:00:00',
                'BestellNr' => 'Event: Team Meeting',
                'Liefertermin' => '2025-04-28',
                'KundenNr' => '',
                'KundenMatchcode' => 'CALENDAR',
                'Status' => 1,
                'ShowPos' => 1,
                'Tags' => [['lTagId' => 4, 'szName' => 'Neu']],
                'Positionen' => [
                    1 => [
                        'PosNr' => 1,
                        'ArtikelId' => 0,
                        'ArtikelNr' => '',
                        'Artikel_Bezeichnung' => 'Team Meeting',
                        'Artikel_Menge' => 1,
                        'Artikel_LagerId' => 0
                    ]
                ]
            ],
            'E_100001' => [
                'AuftragId' => 'E_100001',
                'AuftragsNr' => 'EVENT_002',
                'AuftragsKennung' => 1,
                'Datum_Erfassung' => '2025-04-29 14:00:00',
                'BestellNr' => 'Event: Maintenance',
                'Liefertermin' => '2025-04-29',
                'KundenNr' => '',
                'KundenMatchcode' => 'CALENDAR',
                'Status' => 1,
                'ShowPos' => 1,
                'Tags' => [['lTagId' => 4, 'szName' => 'Neu']],
                'Positionen' => [
                    1 => [
                        'PosNr' => 1,
                        'ArtikelId' => 0,
                        'ArtikelNr' => '',
                        'Artikel_Bezeichnung' => 'Maintenance',
                        'Artikel_Menge' => 1,
                        'Artikel_LagerId' => 0
                    ]
                ]
            ]
        ];
        return $mockEvents;
    }

    public function Print_Events($Events) {
        $content = '';
        foreach ($Events as $event) {
            if (!$event['AuftragId'] || strpos($event['AuftragId'], 'E_') !== 0) {
                error_log("Invalid event AuftragId: " . print_r($event, true));
                continue;
            }
            $Liefertermin = new DateTimeImmutable($event['Liefertermin']);
            $Erfassungsdatum = new DateTimeImmutable($event['Datum_Erfassung']);
            $bc = 'fb7d44'; // Neu
            $StrKunde = '<span style="padding-left:2px;font-size:14px;">' . $event['KundenMatchcode'] . '</span>';
            $ShowPos = $event['ShowPos'] ? '' : 'display:none;';
            $StateImg = $event['ShowPos'] ? 'up.png' : 'dn.png';
            $StrTags = '';
            foreach ($event['Tags'] as $tag) {
                $StrTags .= '[' . $tag['lTagId'] . ':' . $tag['szName'] . ']';
            }
            $style_LT = '';
            $today = new DateTime(date('Y-m-d'));
            $dif_days = date_diff($today, $Liefertermin)->format('%r%a');
            if ($dif_days <= 0) $style_LT = 'color:red;';
            elseif ($dif_days > 0 && $dif_days <= 3) $style_LT = 'color:#e55f00;';

            $content .= '<li class="no-drag" id="' . $event['AuftragId'] . '" style="min-height:34px;" data-status="' . $event['Status'] . '" data-draggable="false">';
            $content .= '<div style="height:5px;background-color:#' . $bc . '"></div>
                <div class="order-container">
                    <div class="table-orderinfo">
                        <div class="table-row-orderinfo">
                            <div class="table-cell-kunde">' . $StrKunde . '</div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-liefertermin" style="padding-right:5px;' . $style_LT . '">' . $Liefertermin->format('d.m.y') . '</div>
                        </div>
                        <div class="table-row-orderinfo" style="position: relative; top:-4px;">
                            <div class="table-cell-AuftragsNr">EVENT ' . $event['AuftragsNr'] . ' ' . $Erfassungsdatum->format('d.m.y') . '</div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-BestellNr">Bst: ' . $event['BestellNr'] . '</div>
                        </div>
                    </div>';
            $content .= '<div style="width:auto;height:auto;text-align:center;border:0px solid blue;margin-top:-26px;">';
            $content .= '<img style="position: relative; top:-3px;" class="apply4job" id="' . $event['AuftragId'] . '" height="12px" src="img/UI/' . $StateImg . '">';
            $content .= '<!-- Show/Hide -->
                    <div style="' . $ShowPos . '">
                        <div class="table-positions" style="margin-top:6px;">';
            foreach ($event['Positionen'] as $pos) {
                $content .= '<div class="table-row-artikelpos" ArtikelId="' . $pos['ArtikelId'] . '">
                    <div class="artikel-pos-artikelnr">' . $pos['ArtikelNr'] . '</div>
                    <div class="artikel-pos-bez">' . $pos['Artikel_Bezeichnung'] . '</div>
                    <div class="artikel-pos-verfuegbar"></div>
                    <div class="artikel-pos-menge">' . number_format($pos['Artikel_Menge'], 0, ',', '.') . '</div>
                    <div class="artikel-pos-check"></div>
                </div>';
            }
            $content .= '</div></div></div></div></li>';
        }
        return $content;
    }
}
?>