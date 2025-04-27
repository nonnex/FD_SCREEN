<?php
class Mock_Lx_Lager {
    public function Get_Mindestbestand_All() {
        return [
            'A001' => [
                'lArtikelId' => 'A001',
                'Mindestbestand' => 20,
                'Verfuegbar' => 5
            ],
            'A002' => [
                'lArtikelId' => 'A002',
                'Mindestbestand' => 15,
                'Verfuegbar' => 10
            ]
        ];
    }

    public function Get_LagerInfo_By_Id($ArtikelId) {
        $data = $this->Get_Mindestbestand_All();
        return [
            'Verfuegbar' => $data[$ArtikelId]['Verfuegbar'] ?? 0,
            'Mindestbestand' => $data[$ArtikelId]['Mindestbestand'] ?? 0
        ];
    }
}

class Mock_Lx_Artikel {
    public function Get_Artikel_Info($ArtikelId) {
        $mockData = [
            'A001' => [
                'SheetNr' => 'A001',
                'ArtikelNr' => 'ART001',
                'Bezeichnung' => 'Produkt A',
                'bStatus_lager' => 1
            ],
            'A002' => [
                'SheetNr' => 'A002',
                'ArtikelNr' => 'ART002',
                'Bezeichnung' => 'Produkt B',
                'bStatus_lager' => 1
            ]
        ];
        return $mockData[$ArtikelId] ?? [];
    }
}

class Mock_Lx_Orders_Virtual {
    private $Lx_Lager;
    private $Lx_Artikel;

    public function __construct() {
        $this->Lx_Lager = new Mock_Lx_Lager();
        $this->Lx_Artikel = new Mock_Lx_Artikel();
    }

    public function CreateMindestbestandOrder() {
        $data = $this->Lx_Lager->Get_Mindestbestand_All();
        $ret = [];
        foreach ($data as $key => $val) {
            if ($val['lArtikelId']) {
                $lagerInfo = $this->Lx_Lager->Get_LagerInfo_By_Id($val['lArtikelId']);
                if ($lagerInfo['Verfuegbar'] < $lagerInfo['Mindestbestand']) {
                    $ret[] = [
                        'lArtikelId' => $val['lArtikelId'],
                        'Lager' => $lagerInfo
                    ];
                }
            }
        }

        $Liefertermin = new DateTime();
        $Liefertermin = $Liefertermin->modify('+1 month +3 day')->format('Y-m-d');

        $MinOrder = [
            'AuftragId' => 'V_99999',
            'AuftragsNr' => '99999',
            'AuftragsKennung' => 1,
            'Datum_Erfassung' => date('Y-m-d H:i:s'),
            'BestellNr' => 'Mindestbestand',
            'Liefertermin' => $Liefertermin,
            'KundenNr' => '',
            'KundenMatchcode' => 'FERRODOM',
            'Status' => 2,
            'ShowPos' => 1,
            'Tags' => [
                ['lTagId' => 2, 'szName' => 'Produktion']
            ],
            'Positionen' => []
        ];

        $p = 2;
        foreach ($ret as $val) {
            $MinData = $this->Lx_Artikel->Get_Artikel_Info($val['lArtikelId']);
            $MinOrder['Positionen'][$p] = [
                'PosNr' => $p,
                'ArtikelId' => $MinData['SheetNr'],
                'ArtikelNr' => $MinData['ArtikelNr'],
                'Artikel_Bezeichnung' => $MinData['Bezeichnung'],
                'Artikel_Menge' => $val['Lager']['Mindestbestand'] - $val['Lager']['Verfuegbar'],
                'Artikel_LagerId' => 1
            ];
            $p++;
        }

        return $MinOrder;
    }

    public function GetOrderContainer($Data, $Status = 0) {
        $content = '';
        foreach ($Data as $val) {
            if ($Status && $val['Status'] != $Status) {
                continue;
            }
            $Liefertermin = new DateTimeImmutable($val['Liefertermin']);
            $Erfassungsdatum = new DateTimeImmutable($val['Datum_Erfassung']);
            $bc = '2a92bf'; // Produktion
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
            $TagIcon = 'inprod.svg';

            $content .= '<li class="no-drag" id="' . $val['AuftragId'] . '" style="min-height:34px;" data-status="' . $val['Status'] . '" data-draggable="false">';
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
                            <div class="table-cell-AuftragsNr">AB ' . $val['AuftragsNr'] . ' ' . $Erfassungsdatum->format('d.m.y') . '</div>
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
                    <div class="artikel-pos-verfuegbar">(' . ($pos['Artikel_Menge'] + 5) . ')</div>
                    <div class="artikel-pos-menge">' . number_format($pos['Artikel_Menge'], 0, ',', '.') . '</div>
                    <div class="artikel-pos-check"><img src="img/UI/check_inproc.png" width="8px" height="8px" style="margin-top:-1px;" /></div>
                </div>';
            }
            $content .= '</div></div></div></div></li>';
        }
        return $content;
    }
}
?>