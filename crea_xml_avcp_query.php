<?php
/*
 * This file is part of project AVCP-Xml that can be found at:
 * https://github.com/provinciadicremona/AVCP-Xml
 * 
 * © 2013 Claudio Roncaglio <claudio.roncaglio@provincia.cremona.it>
 * © 2013 Gianni Bassini <gianni.bassini@provincia.cremona.it>
 * © 2013 Provincia di Cremona <sito@provincia.cremona.it>
 * 
 * SPDX-License-Identifier: GPL-3.0-only
*/
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
require_once './app/config.php';
$XML_FILE = null;
$anno_rif = (int) $_GET['anno'];
if (empty($anno_rif)) {
    die('Anno non corretto');
}
$dataPubb = $anno_rif + 1 . "-01-31";
header("Content-Disposition: attachment; filename=avcp_dataset_" . $anno_rif . ".xml");
header("Content-Type: application/force-download");
header("content-type: application/xml");
echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>' . PHP_EOL;
$date_agg = date("Y-m-d");
$date_agg_full = date("Y-m-d H:i:s");
$query_lotti = "SELECT * FROM avcp_lotto WHERE anno = '" . $anno_rif . "' AND sceltaContraente != '00-DA DEFINIRE'";
$result_lotti = $db->query($query_lotti);
$number_lotti = $result_lotti->num_rows;

echo '<legge190:pubblicazione xsi:schemaLocation="legge190_1_0 datasetAppaltiL190.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:legge190="legge190_1_0">
<metadata>
<titolo> Pubblicazione 1 legge 190</titolo>
<abstract> Pubblicazione 1 legge 190 anno 1 rif. 2010' . ' aggiornamento del ' . $date_agg_full . '</abstract>
<dataPubblicazioneDataset>' . $dataPubb . '</dataPubblicazioneDataset>
<entePubblicatore>' . stripslashes(ENTE_PROPONENTE) . '</entePubblicatore>
<dataUltimoAggiornamentoDataset>' . $date_agg . '</dataUltimoAggiornamentoDataset>
<annoRiferimento>' . $anno_rif . '</annoRiferimento>';
if (URL_XML_FILE_ANNUALE == 'NO') {
    echo '<urlFile>' . URL_XML_FILE . '</urlFile>' . PHP_EOL;
} else {
    $url = strpos(URL_XML_FILE_ANNUALE, '{{anno}}') === false
        ? URL_XML_FILE_ANNUALE . $anno_rif // Per la retrocompatibilità.
        : str_replace('{{anno}}', $anno_rif, URL_XML_FILE_ANNUALE);

    echo '<urlFile>' . $url . '.xml</urlFile>' . PHP_EOL;
}

echo '<licenza>' . LICENZA . '</licenza>'.PHP_EOL.'</metadata>' . PHP_EOL;
echo '<data>' . PHP_EOL;
while ($lotto = $result_lotti->fetch_assoc()) {
    foreach ($lotto as $key => $value) {
        $$key = stripslashes($value);
    }
    $oggetto = htmlspecialchars($oggetto, ENT_NOQUOTES, 'UTF-8');
    $XML_FILE .= "<lotto>\n";
    $XML_FILE .= "<cig>" . $cig . "</cig>\n";
    $XML_FILE .= "<strutturaProponente>\n";
    $XML_FILE .= "<codiceFiscaleProp>" . $codiceFiscaleProp . "</codiceFiscaleProp>\n";
    $XML_FILE .= "<denominazione>" . stripslashes($denominazione) . "</denominazione>\n";
    $XML_FILE .= "</strutturaProponente>\n";
    $XML_FILE .= "<oggetto>" . $oggetto . "</oggetto>\n";
    $XML_FILE .= "<sceltaContraente>" . $sceltaContraente . "</sceltaContraente>\n";

    // ### PARTECIPANTI ######

    $XML_FILE .= "<partecipanti>\n";
    $raggruppamento_old = 1;
    $raggruppamento_start = 0;
    $XML_RAGG = "<raggruppamento>\n";
    $XML_PART = null;
    $query_partecipanti = "SELECT * FROM avcp_xml_ditte WHERE id = '" . $id . "' AND funzione = '01-PARTECIPANTE'";
    $result_partecipanti = $db->query($query_partecipanti);
    $number_partecipanti = $result_partecipanti->num_rows;
    $flag_first_ragg = true;
    while ($partecipante = $result_partecipanti->fetch_assoc()) {
        foreach ($partecipante as $key => $value) {
            $$key = stripslashes($value);
        }
        $ragioneSociale = htmlspecialchars($ragioneSociale, ENT_NOQUOTES, 'UTF-8');
        if ($raggruppamento == 0) {
            $XML_PART .= "<partecipante>\n";
            if ($estero == 0) {
                $XML_PART .= "<codiceFiscale>" . $codiceFiscale . "</codiceFiscale>\n";
            } else {
                $XML_PART .= "<identificativoFiscaleEstero>" . $codiceFiscale . "</identificativoFiscaleEstero>\n";
            }
            $XML_PART .= "<ragioneSociale>" . $ragioneSociale . "</ragioneSociale>\n";
            $XML_PART .= "</partecipante>\n";
        } else {
            if ($flag_first_ragg == true) {
                $raggruppamento_old = $raggruppamento;
                $flag_first_ragg = false;
            }
            if ($raggruppamento != $raggruppamento_old) {
                $XML_RAGG .= "</raggruppamento>\n";
                $XML_RAGG .= "<raggruppamento>\n";
            }
            $XML_RAGG .= "<membro>\n";
            if ($estero == 0) {
                $XML_RAGG .= "<codiceFiscale>" . $codiceFiscale . "</codiceFiscale>\n";
            } else {
                $XML_RAGG .= "<identificativoFiscaleEstero>" . $codiceFiscale . "</identificativoFiscaleEstero>\n";
            }
            $XML_RAGG .= "<ragioneSociale>" . $ragioneSociale . "</ragioneSociale>\n";
            $XML_RAGG .= "<ruolo>" . $ruolo . "</ruolo>\n";
            $XML_RAGG .= "</membro>\n";
            $raggruppamento_old = $raggruppamento;
            $raggruppamento_start = 1;
        }
    } // fine while partecipanti
    $result_partecipanti->free(); // pulisco partecipanti
    if ($raggruppamento_start == 1) {
        $XML_RAGG .= "</raggruppamento>\n";
        $XML_FILE .= $XML_RAGG;
    }
    $XML_FILE .= $XML_PART;
    $XML_FILE .= "</partecipanti>\n";

    // ### AGGIUDICATARI ######

    $XML_FILE .= "<aggiudicatari>\n";

    $raggruppamento_old = 1;
    $raggruppamento_start = 0;
    // $XML_RAGG ="<aggiudicatarioRaggruppamento>\n";
    $XML_RAGG = null;
    $XML_AGG = null;
    $query_aggiudicatari = 'SELECT * FROM avcp_xml_ditte WHERE id = "' . $id . '" AND funzione = "02-AGGIUDICATARIO" ORDER BY raggruppamento DESC';
    $result_aggiudicatari = $db->query($query_aggiudicatari);
    $number_aggiudicatari = $result_aggiudicatari->num_rows;
    while ($aggiudicatario = $result_aggiudicatari->fetch_assoc()) {
        foreach ($aggiudicatario as $key => $value) {
            $$key = stripslashes($value);
        }
        $ragioneSociale = htmlspecialchars($ragioneSociale, ENT_NOQUOTES, 'UTF-8');
        if ($raggruppamento == 0) {
            $XML_AGG .= "<aggiudicatario>\n";
            if ($estero == 0) {
                $XML_AGG .= "<codiceFiscale>" . $codiceFiscale . "</codiceFiscale>\n";
            } else {
                $XML_AGG .= "<identificativoFiscaleEstero>" . $codiceFiscale . "</identificativoFiscaleEstero>\n";
            }
            $XML_AGG .= "<ragioneSociale>" . $ragioneSociale . "</ragioneSociale>\n";
            $XML_AGG .= "</aggiudicatario>\n";
        } else {
            if ($raggruppamento_start == 0) {
                $XML_RAGG .= "<aggiudicatarioRaggruppamento>\n";
            }
            if ($raggruppamento != $raggruppamento_old && $raggruppamento_start == 1) {
                $XML_RAGG .= "</aggiudicatarioRaggruppamento>\n";
                $XML_RAGG .= "<aggiudicatarioRaggruppamento>\n";
            }
            $XML_RAGG .= "<membro>\n";
            if ($estero == 0) {
                $XML_RAGG .= "<codiceFiscale>" . $codiceFiscale . "</codiceFiscale>\n";
            } else {
                $XML_RAGG .= "<identificativoFiscaleEstero>" . $codiceFiscale . "</identificativoFiscaleEstero>\n";
            }
            $XML_RAGG .= "<ragioneSociale>" . $ragioneSociale . "</ragioneSociale>\n";
            $XML_RAGG .= "<ruolo>" . $ruolo . "</ruolo>\n";
            $XML_RAGG .= "</membro>\n";
            $raggruppamento_old = $raggruppamento;
            $raggruppamento_start = 1;
        }
    } // fine while aggiudicatari
    $result_aggiudicatari->free(); // pulisco aggiudicatari
    if ($raggruppamento_start == 1) {
        $XML_RAGG .= "</aggiudicatarioRaggruppamento>\n";
    }
    $XML_FILE .= $XML_RAGG . $XML_AGG;
    $XML_FILE .= "</aggiudicatari>\n";

    // ####### importi e tempi #####
    $XML_FILE .= "<importoAggiudicazione>" . $importoAggiudicazione . "</importoAggiudicazione>\n";
    $XML_FILE .= "<tempiCompletamento>\n";
    if (!empty($dataInizio) && $dataInizio != '0000-00-00') {
        $XML_FILE .= "<dataInizio>" . $dataInizio . "</dataInizio>\n";
    }
    if (!empty($dataUltimazione) && $dataUltimazione != '0000-00-00') {
        $XML_FILE .= "<dataUltimazione>" . $dataUltimazione . "</dataUltimazione>\n";
    }
    $XML_FILE .= "</tempiCompletamento>\n";
    $XML_FILE .= "<importoSommeLiquidate>" . $importoSommeLiquidate . "</importoSommeLiquidate>";
    $XML_FILE .= "</lotto>\n";
} // fine while lotti
$result_lotti->free(); // pulisco lotti
echo $XML_FILE;
echo '</data>' . PHP_EOL;
echo '</legge190:pubblicazione>';

