<?php
class Mock_Lx_Orders {
    private $dummyLxDataAB = [
        10001 => [
            'AuftragId' => 10001,
            'AuftragsNr' => 10001,
            'AuftragsKennung' => 1,
            'Datum_Erfassung' => '2025-04-20 10:00:00',
            'BestellNr' => 'PO-001',
            'Liefertermin' => '2025-05-01 00:00:00',
            'KundenNr' => 'CUST001',
            'KundenMatchcode' => 'Customer One',
            'Status' => 1,
            'ShowPos' => 1,
            'Tags' => [['lTagId' => 4, 'szName' => 'Neu']],
            'Positionen' => [
                1 => [
                    'PosNr' => 1,
                    'ArtikelId' => 'ART001',
                    'ArtikelNr' => 'A001',
                    'Artikel_Bezeichnung' => 'Widget A',
                    'Artikel_Menge' => 10,
                    'Artikel_LagerId' => 1
                ],
                2 => [
                    'PosNr' => 2,
                    'ArtikelId' => 'ART002',
                    'ArtikelNr' => 'A002',
                    'Artikel_Bezeichnung' => 'Widget B',
                    'Artikel_Menge' => 5,
                    'Artikel_LagerId' => 1
                ]
            ],
            'szUserdefined1' => '',
            'szUserdefined2' => '',
            'szUserdefined3' => ''
        ],
        10002 => [
            'AuftragId' => 10002,
            'AuftragsNr' => 10002,
            'AuftragsKennung' => 1,
            'Datum_Erfassung' => '2025-04-21 12:00:00',
            'BestellNr' => 'PO-002',
            'Liefertermin' => '2025-05-02 00:00:00',
            'KundenNr' => 'CUST002',
            'KundenMatchcode' => 'Customer Two',
            'Status' => 2,
            'ShowPos' => 1,
            'Tags' => [['lTagId' => 2, 'szName' => 'Produktion']],
            'Positionen' => [
                1 => [
                    'PosNr' => 1,
                    'ArtikelId' => 'ART003',
                    'ArtikelNr' => 'A003',
                    'Artikel_Bezeichnung' => 'Widget C',
                    'Artikel_Menge' => 15,
                    'Artikel_LagerId' => 1
                ]
            ],
            'szUserdefined1' => '',
            'szUserdefined2' => '',
            'szUserdefined3' => ''
        ],
        10003 => [
            'AuftragId' => 10003,
            'AuftragsNr' => 10003,
            'AuftragsKennung' => 1,
            'Datum_Erfassung' => '2025-04-22 14:00:00',
            'BestellNr' => 'PO-003',
            'Liefertermin' => '2025-05-03 00:00:00',
            'KundenNr' => 'CUST003',
            'KundenMatchcode' => 'Customer Three',
            'Status' => 3,
            'ShowPos' => 1,
            'Tags' => [['lTagId' => 5, 'szName' => 'Versandbereit']],
            'Positionen' => [
                1 => [
                    'PosNr' => 1,
                    'ArtikelId' => 'ART004',
                    'ArtikelNr' => 'A004',
                    'Artikel_Bezeichnung' => 'Widget D',
                    'Artikel_Menge' => 20,
                    'Artikel_LagerId' => 1
                ]
            ],
            'szUserdefined1' => '',
            'szUserdefined2' => '',
            'szUserdefined3' => ''
        ]
    ];

    private $dummyLxDataLS = [
        20001 => [
            'AuftragId' => 20001,
            'AuftragsNr' => 20001,
            'AuftragsKennung' => 2,
            'Datum_Erfassung' => '2025-04-23 09:00:00',
            'BestellNr' => 'PO-004',
            'Liefertermin' => '2025-04-27 00:00:00',
            'KundenNr' => 'CUST004',
            'KundenMatchcode' => 'Customer Four',
            'Status' => 4,
            'ShowPos' => 1,
            'Tags' => [['lTagId' => 1, 'szName' => 'Versendet']],
            'Positionen' => [
                1 => [
                    'PosNr' => 1,
                    'ArtikelId' => 'ART005',
                    'ArtikelNr' => 'A005',
                    'Artikel_Bezeichnung' => 'Widget E',
                    'Artikel_Menge' => 25,
                    'Artikel_LagerId' => 1
                ]
            ],
            'szUserdefined1' => '27.04.2025 15:00',
            'szUserdefined2' => 'Schenker',
            'szUserdefined3' => 'SST123456'
        ]
    ];

    private $dummyMinOrder = [
        'AuftragId' => 9999,
        'AuftragsNr' => 9999,
        'AuftragsKennung' => '',
        'Datum_Erfassung' => '2025-04-27 08:00:00',
        'BestellNr' => 'Mindestbestand',
        'Liefertermin' => '2025-06-03 00:00:00',
        'KundenNr' => '',
        'KundenMatchcode' => 'FERRODOM',
        'Status' => 2,
        'ShowPos' => 1,
        'Tags' => [['lTagId' => 2, 'szName' => 'Produktion']],
        'Positionen' => [
            2 => [
                'PosNr' => 2,
                'ArtikelId' => 'ART006',
                'ArtikelNr' => 'A006',
                'Artikel_Bezeichnung' => 'Widget F',
                'Artikel_Menge' => 30,
                'Artikel_LagerId' => 1
            ],
            3 => [
                'PosNr' => 3,
                'ArtikelId' => 'ART007',
                'ArtikelNr' => 'A007',
                'Artikel_Bezeichnung' => 'Widget G',
                'Artikel_Menge' => 40,
                'Artikel_LagerId' => 1
            ]
        ],
        'szUserdefined1' => '',
        'szUserdefined2' => '',
        'szUserdefined3' => ''
    ];

    public function GetAllOpenOrdersFromLX($AuftragsKennung) {
        return ($AuftragsKennung == 1) ? $this->dummyLxDataAB : $this->dummyLxDataLS;
    }

    public function CreateMindestbestandOrder() {
        return $this->dummyMinOrder;
    }

    public function GetOrderContainer($Data, $Status = 0) {
        $key_values = array_column($Data, 'Liefertermin');
        array_multisort($key_values, SORT_ASC, $Data);

        if ($Status) {
            $Data = array_filter($Data, function($item) use ($Status) {
                return $item['Status'] == $Status;
            });
        }

        $content = '';
        foreach ($Data as &$val) {
            $Liefertermin = new DateTimeImmutable($val['Liefertermin']);
            $Erfassungsdatum = new DateTimeImmutable($val['Datum_Erfassung']);
            $bc = match ($val['Status']) {
                1 => 'fb7d44', // NEW
                2 => '2a92bf', // PRODUCTION
                3 => 'f4ce46', // READY TO SHIP
                4 => '00b961', // SHIPPED
                default => '00b961'
            };

            $StrKunde = '<span style="padding-left:2px;font-size:14px;">' . $val['KundenMatchcode'] . '</span>';
            $ShowPos = $val['ShowPos'] ? '' : 'display:none;';
            $StateImg = $val['ShowPos'] ? 'up.png' : 'dn.png';
            $StrTags = '';
            foreach ($val['Tags'] as $tags) {
                $StrTags .= '[' . $tags['lTagId'] . ':' . $tags['szName'] . ']';
            }

            $TagIcon = match ($val['Tags'][0]['lTagId']) {
                4 => 'neu.svg',
                2 => 'inprod.svg',
                5 => 'vorb.svg',
                1 => 'delivery_0.svg',
                default => ''
            };
            $TagIconEn = ($val['AuftragsKennung'] == 2 && $val['Tags'][0]['lTagId'] == 5) ? '' : 'disabled';
            $KennungStr = ($val['AuftragsKennung'] == 2) ? 'LS' : 'AB';
            $StrVersendetAm = isset($val['szUserdefined1']) && $val['szUserdefined1'] ? $val['szUserdefined1'] . '(' . (isset($val['szUserdefined2']) ? $val['szUserdefined2'] : '') . ') ' . (isset($val['szUserdefined3']) ? $val['szUserdefined3'] : '') : '';
            $StrVersand = '<form id="f_' . $val['AuftragId'] . '" name="f_' . $val['AuftragId'] . '" method="POST" class="delivery-form">
                <input type="hidden" class="form-control" id="AuftragId" name="AuftragId" value="' . $val['AuftragId'] . '" />
                <input type="hidden" class="form-control" id="Tag" name="Tag" value="' . $val['Tags'][0]['lTagId'] . '" />
                <input type="image" src="img/UI/' . $TagIcon . '" class="delivery-button confirm" value="" id="delivery_button" name="delivery_button" ' . $TagIconEn . ' />
                </form>';

            $style_LT = '';
            $today = new DateTime(date('Y-m-d'));
            $dif_days = date_diff($today, $Liefertermin)->format('%r%a');
            if ($dif_days <= 0) $style_LT = 'color:red;';
            elseif ($dif_days <= 3) $style_LT = 'color:#e55f00;';

            // Debug: Log status for each order
            error_log("Rendering order {$val['AuftragId']} with status {$val['Status']}");

            $content .= '<li class="no-drag" id="' . $val['AuftragId'] . '" style="min-height:34px;" data-status="' . $val['Status'] . '">
                <div style="height:5px;background-color:#' . $bc . '"></div>
                <div class="order-container">
                    <div class="table-orderinfo">
                        <div class="table-row-orderinfo">
                            <div class="table-cell-kunde">' . $StrKunde . '</div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-delivery">' . $StrVersand . '</div>
                            <div class="table-cell-liefertermin" style="padding-right:5px;' . $style_LT . '">' . $Liefertermin->format('d.m.y') . '</div>
                        </div>
                        <div class="table-row-orderinfo" style="position: relative; top:-4px;">
                            <div class="table-cell-AuftragsNr">' . $KennungStr . ' ' . $val['AuftragsNr'] . ' ' . $Erfassungsdatum->format('d.m.y') . '</div>
                            <div class="table-cell-delivery" style="font-size:6px;">' . $StrVersendetAm . '</div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-BestellNr">Bst: ' . $val['BestellNr'] . '</div>
                        </div>
                    </div>
                    <div style="width:auto;height:auto;text-align:center;border:0px solid blue;margin-top:-26px;">
                        <img style="position: relative; top:-3px;" class="apply4job" id="' . $val['AuftragId'] . '" height="12px" src="img/UI/' . $StateImg . '">
                        <div style="' . $ShowPos . '">
                            <div class="table-positions" style="margin-top:6px;">';

            foreach ($val['Positionen'] as &$pos) {
                $str_verfueg = '(' . number_format(100, 0, ',', '.') . ')';
                $style_v = 'color:green;';
                $state_check = '<a id="foo" href="lager_artikel.php?ArtikelId=' . $pos['ArtikelId'] . '&AuftragsNr=' . $val['AuftragsNr'] . '&PosNr=' . $pos['PosNr'] . '&AuftragsKennung=' . $val['AuftragsKennung'] . '&Ref=index.php">
                    <img src="img/UI/check_done.png" width="8px" height="8px" style="margin-top:-1px;" class="apply4job2" />
                    </a>';

                $content .= '<div style="' . $style_v . '" class="table-row-artikelpos" ArtikelId="' . $pos['ArtikelId'] . '">
                    <div class="artikel-pos-artikelnr">' . $pos['ArtikelNr'] . '</div>
                    <div class="artikel-pos-bez">' . $pos['Artikel_Bezeichnung'] . '</div>
                    <div class="artikel-pos-verfuegbar" style="' . $style_v . '">' . $str_verfueg . '</div>
                    <div class="artikel-pos-menge">' . number_format($pos['Artikel_Menge'], 0, ',', '.') . '</div>
                    <div class="artikel-pos-check">' . $state_check . '</div>
                </div>';
            }

            $content .= '</div></div></div></div></li>';
        }

        return $content;
    }
}

class Mock_Lx_Events {
    private $dummyEvents = [
        ['title' => 'Team Meeting', 'date' => '2025-04-27', 'description' => 'Weekly team sync'],
        ['title' => 'Maintenance', 'date' => '2025-04-28', 'description' => 'Scheduled equipment check']
    ];

    public function Get_Events() {
        return $this->dummyEvents;
    }

    public function Print_Events($events) {
        $content = '';
        foreach ($events as $event) {
            // Add data-draggable="false" to explicitly disable dragging
            $content .= '<li class="no-drag" data-draggable="false" style="min-height:34px;">
                <div style="height:5px;background-color:#fb7d44"></div>
                <div class="order-container">
                    <div class="table-orderinfo">
                        <div class="table-row-orderinfo">
                            <div class="table-cell-kunde">' . $event['title'] . '</div>
                            <div class="table-cell-liefertermin" style="padding-right:5px;">' . $event['date'] . '</div>
                        </div>
                        <div class="table-row-orderinfo" style="position: relative; top:-4px;">
                            <div class="table-cell-AuftragsNr">' . $event['description'] . '</div>
                        </div>
                    </div>
                </div>
            </li>';
        }
        return $content;
    }
}
?>