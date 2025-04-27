<?php
class Mock_Lx_Orders {
    public function GetAllOpenOrdersFromLX($Kennung = 1) {
        $mockData = [];
        if ($Kennung === 1) {
            $mockData = [
                '10001' => [
                    'AuftragId' => '10001',
                    'AuftragsNr' => '10001',
                    'AuftragsKennung' => 1,
                    'Datum_Erfassung' => '2025-04-01 10:00:00',
                    'BestellNr' => 'BST10001',
                    'Liefertermin' => '2025-05-01',
                    'KundenNr' => 'K001',
                    'KundenMatchcode' => 'Kunde A',
                    'Status' => 1,
                    'ShowPos' => 1,
                    'Tags' => [['lTagId' => 4, 'szName' => 'Neu']],
                    'Positionen' => [
                        1 => [
                            'PosNr' => 1,
                            'ArtikelId' => 'A001',
                            'ArtikelNr' => 'ART001',
                            'Artikel_Bezeichnung' => 'Produkt A',
                            'Artikel_Menge' => 10,
                            'Artikel_LagerId' => 1
                        ]
                    ]
                ],
                '10002' => [
                    'AuftragId' => '10002',
                    'AuftragsNr' => '10002',
                    'AuftragsKennung' => 1,
                    'Datum_Erfassung' => '2025-04-02 12:00:00',
                    'BestellNr' => 'BST10002',
                    'Liefertermin' => '2025-05-02',
                    'KundenNr' => 'K002',
                    'KundenMatchcode' => 'Kunde B',
                    'Status' => 2,
                    'ShowPos' => 1,
                    'Tags' => [['lTagId' => 2, 'szName' => 'Produktion']],
                    'Positionen' => [
                        1 => [
                            'PosNr' => 1,
                            'ArtikelId' => 'A002',
                            'ArtikelNr' => 'ART002',
                            'Artikel_Bezeichnung' => 'Produkt B',
                            'Artikel_Menge' => 5,
                            'Artikel_LagerId' => 1
                        ]
                    ]
                ]
            ];
        } elseif ($Kennung === 2) {
            $mockData = [
                '10003' => [
                    'AuftragId' => '10003',
                    'AuftragsNr' => '10003',
                    'AuftragsKennung' => 2,
                    'Datum_Erfassung' => '2025-04-03 14:00:00',
                    'BestellNr' => 'BST10003',
                    'Liefertermin' => '2025-05-03',
                    'KundenNr' => 'K003',
                    'KundenMatchcode' => 'Kunde C',
                    'Status' => 4,
                    'ShowPos' => 1,
                    'Tags' => [['lTagId' => 1, 'szName' => 'Versendet']],
                    'Positionen' => [
                        1 => [
                            'PosNr' => 1,
                            'ArtikelId' => 'A003',
                            'ArtikelNr' => 'ART003',
                            'Artikel_Bezeichnung' => 'Produkt C',
                            'Artikel_Menge' => 8,
                            'Artikel_LagerId' => 1
                        ]
                    ]
                ],
                '10004' => [
                    'AuftragId' => '10004',
                    'AuftragsNr' => '10004',
                    'AuftragsKennung' => 2,
                    'Datum_Erfassung' => '2025-04-04 16:00:00',
                    'BestellNr' => 'BST10004',
                    'Liefertermin' => '2025-05-04',
                    'KundenNr' => 'K004',
                    'KundenMatchcode' => 'Kunde D',
                    'Status' => 4,
                    'ShowPos' => 1,
                    'Tags' => [['lTagId' => 1, 'szName' => 'Versendet']],
                    'Positionen' => [
                        1 => [
                            'PosNr' => 1,
                            'ArtikelId' => 'A004',
                            'ArtikelNr' => 'ART004',
                            'Artikel_Bezeichnung' => 'Produkt D',
                            'Artikel_Menge' => 12,
                            'Artikel_LagerId' => 1
                        ]
                    ]
                ]
            ];
        }
        return $mockData;
    }

    public function GetOrderContainer($Data, $Status = 0) {
        $content = '';
        foreach ($Data as $val) {
            if ($Status && $val['Status'] != $Status) {
                continue;
            }
            $Liefertermin = new DateTimeImmutable($val['Liefertermin']);
            $Erfassungsdatum = new DateTimeImmutable($val['Datum_Erfassung']);
            $bc = match ($val['Status']) {
                1 => 'fb7d44', // Neu
                2 => '2a92bf', // Produktion
                3 => 'f4ce46', // Versandbereit
                4 => '00b961', // Versendet
                default => '00b961'
            };
            $StrKunde = '<span style="padding-left:2px;font-size:14px;">' . $val['KundenMatchcode'] . '</span>';
            $ShowPos = $val['ShowPos'] ? '' : 'display:none;';
            $StateImg = $val['ShowPos'] ? 'up.png' : 'dn.png';
            $StrTags = '';
            foreach ($val['Tags'] as $tags) {
                $StrTags .= '[' . $tags['lTagId'] . ':' . $tags['szName'] . ']';
            }
            $style_LT = '';
            $today = new DateTime(date('Y-m-d'));
            $dif_days = date_diff($today, $Liefertermin)->format('%r%a');
            if ($dif_days <= 0) $style_LT = 'color:red;';
            elseif ($dif_days > 0 && $dif_days <= 3) $style_LT = 'color:#e55f00;';
            $KennungStr = ($val['AuftragsKennung'] == 2) ? 'LS' : 'AB';
            $TagIcon = match ($val['Tags'][0]['lTagId']) {
                4 => 'neu.svg',
                2 => 'inprod.svg',
                5 => 'vorb.svg',
                1 => 'delivery_0.svg',
                6 => 'fakturieren.svg',
                default => 'neu.svg'
            };

            $content .= '<li class="no-drag" id="' . $val['AuftragId'] . '" style="min-height:34px;" data-status="' . $val['Status'] . '">';
            $content .= '<div style="height:5px;background-color:#' . $bc . '"></div>
                <div class="order-container">
                    <div class="table-orderinfo">
                        <div class="table-row-orderinfo">
                            <div class="table-cell-kunde">' . $StrKunde . '</div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-delivery">
                                <form id="f_' . $val['AuftragId'] . '" name="f_' . $val['AuftragId'] . '" method="POST" class="delivery-form">
                                    <input type="hidden" class="form-control" id="AuftragId" name="AuftragId" value="' . $val['AuftragId'] . '" />
                                    <input type="hidden" class="form-control" id="Tag" name="Tag" value="' . $val['Tags'][0]['lTagId'] . '" />
                                    <input type="image" src="img/UI/' . $TagIcon . '" class="delivery-button confirm" value="" id="delivery_button" name="delivery_button" />
                                </form>
                            </div>
                            <div class="table-cell-liefertermin" style="padding-right:5px;' . $style_LT . '">' . $Liefertermin->format('d.m.y') . '</div>
                        </div>
                        <div class="table-row-orderinfo" style="position: relative; top:-4px;">
                            <div class="table-cell-AuftragsNr">' . $KennungStr . ' ' . $val['AuftragsNr'] . ' ' . $Erfassungsdatum->format('d.m.y') . '</div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-BestellNr">Bst: ' . $val['BestellNr'] . '</div>
                        </div>
                    </div>';
            $content .= '<div style="width:auto;height:auto;text-align:center;border:0px solid blue;margin-top:-26px;">';
            $content .= '<img style="position: relative; top:-3px;" class="apply4job" id="' . $val['AuftragId'] . '" height="12px" src="img/UI/' . $StateImg . '">';
            $content .= '<!-- Show/Hide -->
                    <div style="' . $ShowPos . '">
                        <div class="table-positions" style="margin-top:6px;">';
            foreach ($val['Positionen'] as $pos) {
                $content .= '<div class="table-row-artikelpos" ArtikelId="' . $pos['ArtikelId'] . '">
                    <div class="artikel-pos-artikelnr">' . $pos['ArtikelNr'] . '</div>
                    <div class="artikel-pos-bez">' . $pos['Artikel_Bezeichnung'] . '</div>
                    <div class="artikel-pos-verfuegbar">(10)</div>
                    <div class="artikel-pos-menge">' . number_format($pos['Artikel_Menge'], 0, ',', '.') . '</div>
                    <div class="artikel-pos-check"><img src="img/UI/check_done.png" width="8px" height="8px" style="margin-top:-1px;" /></div>
                </div>';
            }
            $content .= '</div></div></div></div></li>';
        }
        return $content;
    }

    public function CreateMindestbestandOrder() {
        // To be implemented in mock_orders_virtual.php
        return [];
    }

    public function GetOrderTags($AuftragId) {
        return [['lTagId' => 4, 'szName' => 'Neu']];
    }

    public function SetOrderTags($AuftragId, $Status) {
        // Mock: Do nothing
    }

    public function SetDeliveryTime($AuftragId, $DeliveryTime) {
        // Mock: Do nothing
    }

    public function GetOrderInfo($AuftragsNr) {
        $mockData = array_merge($this->GetAllOpenOrdersFromLX(1), $this->GetAllOpenOrdersFromLX(2));
        return $mockData[$AuftragsNr] ?? [];
    }

    public function GetOrderPosition($AuftragsNr, $Pos, $AuftragsKennung) {
        return [
            'PosNr' => $Pos,
            'AuftragsNr' => $AuftragsNr,
            'ArtikelNr' => 'ART001',
            'Artikel_Bezeichnung' => 'Produkt A',
            'PosText' => '',
            'Artikel_Einheit' => 'Stk',
            'Artikel_Menge' => 10,
            'Artikel_Preisfaktor' => 10,
            'lArtikelReservierungID' => 1
        ];
    }

    public function UpdateOrderPosition($AuftragsNr, $Pos, $Artikel_Menge, $lArtikelReservierungID, $AuftragsKennung) {
        // Mock: Do nothing
    }

    public function Get_Tracking_Status($SST) {
        return ['Status' => 'Booked'];
    }

    public function FilterByStatus($Data, $Status) {
        $filtered = [];
        foreach ($Data as $k => $v) {
            if ($v['Status'] == $Status) {
                $filtered[$k] = $v;
            }
        }
        return $filtered;
    }
}
?>