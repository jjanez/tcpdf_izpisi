<?php
// ============================================================+
// File name   : http://docker.kis.si:50083/izpis_tl.php
// Begin       : 2017-02-25
// Last Update : 2017-03-05

// Description : Izpis tetovirnega listka
//
// opomba
// Author:  Janez Jeretina

// (c) Copyright:
// Janez Jeretina
// Kmetijski inštitut Slovenije
// Hacquetova ulica 17
// ============================================================+

require_once('/var/www/tcpdf/config/lang/slv.php');
require_once('/var/www/tcpdf/tcpdf.php');
require_once('/var/www/private/glog.php');

// define ('K_PATH_IMAGES', '/var/www/tcpdf/images/');
// define ('K_PATH_CSS', '/css/');

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
// set document information
$pdf -> SetCreator(PDF_CREATOR);
$pdf -> SetAuthor('Janez Jeretina');
$pdf -> SetTitle('Tetovirni listek');
$pdf -> SetSubject('Vaja');
$pdf -> SetKeywords('TCPDF, PDF, example, test, guide');


$v_id_zival = $_GET['v_id_zival'];
$poizvedba0 = OCIParse($connection, "
SELECT TL_VR_DOK_STEV || ' ' || TL_STEV_DOK_STEV tl_id_dok,
       TL_DRZ || ' ' || TL_STEV tl_id_zival,
       tl_ime,
       naredi_pedigre.f_odstotki_krvi_zival (TL_ZIV_ID_SEQ) TL_ZIV_OK,
       TL_KMG_MID,
       GOVEDO_SPLOSNE_PROCEDURE.PREBERI_NASLOV_REJCA_1VRSTA (tl_sifra_rejca)
          rejec,
       TO_CHAR (TL_DAT_ROJ, 'dd.mm.yyyy'),

          TL_SPOL,
       CASE
          WHEN     tl_pasma_opisno_m1 IS NULL
               AND tl_pasma_opisno_m2 IS NULL
               AND tl_pasma_opisno_o1 IS NULL
               AND tl_pasma_opisno_o2 IS NULL
          THEN
             tl_pasma_opisno
          ELSE
                tl_pasma_opisno_m1
             || '/'
             || tl_pasma_opisno_m2
             || '/'
             || tl_pasma_opisno_o1
             || '/'
             || tl_pasma_opisno_o2
       END
          tl_pasma,
       TL_ZIV_DRZ_MATI || ' ' || TL_ZIV_STEV_MATI || ' ' || TL_ZIV_MATI_IME
          tl_id_mati,
          TO_CHAR (TL_DAT_OSEMENITEV, 'dd.mm.yyyy')
       || ' '
       || TL_VR_DOK_OSEM
       || ' '
       || TL_STEV_DOK_OSEM
          tl_id_osem,
          TL_ZIV_DRZ_OCE
       || ' '
       || TL_ZIV_STEV_OCE
       || ' '
       || TL_ZIV_OCE_REP_ST
       || ' '
       || TL_ZIV_OCE_IME
          tl_id_oce,
       TL_SIFRA_PRVI_OZNAC,
       TL_DAT_PRVI_OZNAC || ' ' || tl_ime_priim_prvi_oznac,
       TL_SIFRA_OZNAC,
       1,
          TL_DAT_OZNAC
       || ' '
       || GOVEDO_SPLOSNE_PROCEDURE.PREBERI_IME_KONTROLORJA (TL_SIFRA_OZNAC)
          tl_ime_priim_oznac,
       TL_ZAP_TELITEV,
       CASE
          WHEN     tl_st_m IS NOT NULL
               AND tl_st_z IS NOT NULL
               AND tl_st_n IS NOT NULL
          THEN
             TL_ST_M || ' M ' || TL_ST_Z || ' Ž ' || TL_ST_N || ' N'
          WHEN     tl_st_m IS NOT NULL
               AND tl_st_z IS NOT NULL
               AND tl_st_n IS NULL
          THEN
             TL_ST_M || ' M ' || TL_ST_Z || '  Ž'
          WHEN     tl_st_m IS NOT NULL
               AND tl_st_z IS NULL
               AND tl_st_n IS NOT NULL
          THEN
             TL_ST_M || ' M ' || TL_ST_N || ' N'
          WHEN     tl_st_m IS NULL
               AND tl_st_z IS NOT NULL
               AND tl_st_n IS NOT NULL
          THEN
             TL_ST_Z || '  Ž ' || TL_ST_N || ' N'
          WHEN tl_st_m IS NOT NULL
          THEN
             TL_ST_M || ' M'
          WHEN tl_st_z IS NOT NULL
          THEN
             TL_ST_Z || '  Ž'
          WHEN tl_st_n IS NOT NULL
          THEN
             TL_ST_N || ' N'
       END
          tl_stevilo_telet,
       (  SELECT  opis tl_potek_telitve_opis
                FROM (SELECT zsg_sifra,  zsg_dolgo_ime opis
                        FROM govedo.ZBIRKA_SIFRANTOV_GOVEDO tbl
                       WHERE zsg_ss_id_seq = 18 AND zsg_sifra IN (0, 6)
                      UNION
                      SELECT zsg_sifra,
                             zsg_dolgo_ime opis
                        FROM govedo.ZBIRKA_SIFRANTOV_GOVEDO tbl
                       WHERE zsg_ss_id_seq = 18 AND zsg_sifra > 10)
            where zsg_sifra=TL_POTEK_TELITVE) TL_POTEK_TELITVE,
       TL_TELE_MRTVOROJENO,
       TL_OBSEG_PRSI,
       TL_ROJ_TEZA,
       (SELECT ime_bol
          FROM SIFRANT_SKUPINA_BOLEZNI
               INNER JOIN SIFRANT_PODSK_BOLEZNI
                  ON (SIFRA_SK_BOL = SSB_SIFRA_SK_BOL)
               INNER JOIN
               SIFRANT_BOLEZNI
                  ON     (SSB_SIFRA_SK_BOL = SPB_SSB_SIFRA_SK_BOL)
                     AND (SIFRA_PODSK_BOL = SPB_SIFRA_PODSK_BOL)
         WHERE     sifra_bol = tl_tele_napaka
               AND SSB_SIFRA_SK_BOL = tl_tele_napaka_skup
               AND SIFRA_PODSK_BOL = tl_tele_napaka_podsk)
          tl_tele_napaka,
       tl_tele_napaka_opis,
       tl_sirina,
       tl_kondicija,
       tl_dolzina,
       tl_globina,
       tl_ocena_obl,
       tl_ocena_omi,
       tl_stoja
  FROM tabela_tetovirni_list
 WHERE TL_ZIV_ID_SEQ =kljuc.seq(:v_id_zival)");
 $i = 0;
 OCIBindByName($poizvedba0 , ":v_id_zival", $v_id_zival);
OCIExecute($poizvedba0, OCI_DEFAULT);
while ($row = oci_fetch_array($poizvedba0, OCI_NUM))
 {
    // var_dump($row);
    // var_dump(K_PATH_IMAGES)
    $tl_id_dok = $row[0];
    $tl_zival = $row[1]." ".$row[2];
    $tl_ods_kri = $row[3];
    $tl_rejec = $row[5];
    $tl_rojen = $row[6];
    if ($row[7]==1){$tl_spol='M';}
    elseif ($row[7]==2){$tl_spol='Ž';}else{$tl_spol='N';}

   $tl_mati = $row[9];
    $tl_oce = $row[11];
    $tl_osem = $row[10];
    $tl_zap_tel = $row[17];

     $tl_stevilo =   $row[18] ;
    $tl_potek = $row[19];


    $tl_stanje = $row[20];
      if (TL_TELE_MRTVOROJENO == 1){$tl_stanje='živo';}
      elseif (TL_TELE_MRTVOROJENO == 2){$tl_stanje='mrtvorojeno';}
      elseif (TL_TELE_MRTVOROJENO == 3){$tl_stanje='poginil med porodom';}
      elseif (TL_TELE_MRTVOROJENO == 4){$tl_stanje='poginilo v 48 urah po rojstvu';}
       else{ $tl_stanje='poginilo kasneje zaradi okoliških vplivov';}



    $tl_napaka = $row[23];
    $tl_napaka_opis = $row[24];
    $tl_masa = $row[22];
    $tl_op = $row[21];
    $tl_ozn1 = $row[13];
    $tl_ocenil = $row[16];

    $i++;
    } ;

 if ($tl_ozn1 ==  $tl_ocenil )
    {
    $tl_ozn = "OP.: ".$tl_ozn1;
    }
    elseif  (strlen($tl_ozn1)>1 and strlen($tl_ocenil)>1)
    {
    $tl_ozn = "O.: ".$tl_ozn1;
     $tl_ozn2 = "P.: ".$tl_ocenil;
    }
 elseif (strlen($tl_ozn1)>1)
    {
    $tl_ozn = "O.: ".strlen($tl_ozn1);
    }
 elseif (strlen($tl_ocenil)>1)
    {
    $tl_ozn = "O.: ".$tl_ocenil;
    }


if ( empty($tl_masa) )
{$t_masa=null;
}else{
$t_masa='Rojstna masa:';
}

if (!empty ($tl_napaka))
{$t_napaka='Napaka:';
}

if (empty ($tl_op))
{$t_op=null;
}else{
$t_op='Obseg prsi:';
}

// set default monospaced font
//$pdf -> SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
//$pdf->SetPrintHeader(false);
// set margins
// $pdf -> SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

//$pdf -> SetHeaderMargin(PDF_MARGIN_HEADER);
// $pdf -> SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
// $pdf -> SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf -> setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings
$pdf -> setLanguageArray($l);

// ---------------------------------------------------------
// Set font
$pdf -> SetFont('freesans', '', 7, '', true);

// set color for background
$pdf->SetFillColor(224, 224, 224);

// Add a page
$pdf -> AddPage();

// set cell margins
$pdf->setCellMargins(1, 1, 1, 1);

$lm = 10 ;
$tm = 10;

$txt = 'Kmetijski inštitut Slovenije
Hacquetova ulica 17, 1000 Ljubljana
Telefon: +386 1 28 05 266
El. naslov: govedo@kis.si
Splet: https://www.govedo.si'
;
  $pdf->MultiCell(45, 4, $txt, 0, 'L', 0, 0, $lm+20, $tm, true);
  $pdf->writeHTMLCell(21,21,$lm,$tm,'<img src="/var/www/html/tcpdf/images/kis.jpg"/>');
  $pdf->writeHTMLCell(32,32,$lm+60,$tm,'<img src="/var/www/html/tcpdf/images/kgzs.jpg"/>');
  $pdf->Line($lm+2,$tm+17,$lm+92,$tm+17);

  $pdf -> SetFont('freesans', 'B', 12, '', true);
  $pdf->MultiCell(90, 0, "TELITEV IN OZNAČITEV ŽIVALI  ".$tl_id_dok, 0, 'C', 0, 1, $lm, $tm +29, true);
  $pdf -> SetFont('freesans', '', 10, '', true);
  $pdf->MultiCell(90, 1, "Rejec: ".$tl_rejec,0, 'L', 0, 0, $lm, $tm+39, true,0,false,false,5,'T',true);

  $pdf->MultiCell(90, 0, "Označena žival", 0, 'C', 1,1, $lm,$tm+44 , true);
  $pdf -> SetFont('freesans', 'B', 12, '', true);
  $pdf->MultiCell(150, 0, $tl_zival, 0, 'L', 0,1, $lm,$tm+49 , true);
  $pdf -> SetFont('freesans', '', 10, '', true);

  $pdf->MultiCell(40, 0, "Spol: ", 0, 'L', 0,1, $lm,$tm+54 , true);
  $pdf -> SetFont('freesans', 'B', 12, '', true);
  $pdf->MultiCell(40, 0,  $tl_spol, 0, 'L', 0,1, $lm+9,$tm+53.5 , true);

  $pdf -> SetFont('freesans', '', 10, '', true);
  $pdf->MultiCell(40, 0, "Rojstvo: ", 0, 'L', 0,1, $lm+20,$tm+54 , true);
  $pdf -> SetFont('freesans', 'B', 12, '', true);
  $pdf->MultiCell(40, 0, $tl_rojen, 0, 'L', 0,1, $lm+34,$tm+53.5 , true);

  $pdf -> SetFont('freesans', '', 10, '', true);
  $pdf->MultiCell(80, 0, "Pasma: ".$tl_ods_kri, 0, 'L', 0,1, $lm,$tm+59 , true);
  $pdf -> SetFont('freesans', 'B', 12, '', true);
  $pdf->MultiCell(80, 0, "M: ".$tl_mati, 0, 'L', 0,1, $lm,$tm+64 , true);
  $pdf->MultiCell(80, 0, "O: ".$tl_oce, 0, 'L', 0,1, $lm,$tm+69 , true,  	$fitcell = true);
  $pdf -> SetFont('freesans', '', 10, '', true);
  $pdf->MultiCell(90, 0, "Obrejitev", 0, 'C', 1, 1, $lm, $tm+75 , true);

  $pdf -> SetFont('freesans', 'B', 12, '', true);
  $pdf->MultiCell(80, 0, $tl_osem, 0, 'L', 0,1, $lm,$tm+80 , true);
  $pdf -> SetFont('freesans', '', 10, '', true);

  $pdf->MultiCell(90, 0, "Telitev", 0, 'C', 1,1, $lm,$tm+86 , true);
  $pdf->MultiCell(40, 0, "Zap. telitev: ".$tl_zap_tel, 0, 'L', 0,1, $lm,$tm+92 , true);
  $pdf->MultiCell(40, 0, "Število: ".$tl_stevilo, 0, 'L', 0,1, $lm+50,$tm+92 , true);
  $pdf->MultiCell(90, 0, "Potek: ".$tl_potek, 0, 'L', 0,1, $lm,$tm+97 , true);
  $pdf->MultiCell(90, 0, "Tele je: ".$tl_stanje, 0, 'L', 0,1, $lm,$tm+102 , true);
  $x = $tm+102;
  if (!empty($tl_napaka)){
  $x = $x + 5;
  $pdf->MultiCell(90, 0, "Napake: ".$tl_napaka, 0, 'L', 0,1, $lm,$x, true);
   }
   if (!empty($tl_napaka_opis)){
  $x = $x + 5;
  $pdf->MultiCell(90, 0, $tl_napaka_opis, 0, 'L', 0,1, $lm,$x , true);
  }
   if (!empty($tl_masa) or !empty($tl_op) ){
  $x = $x + 5;
  $pdf->MultiCell(90, 0, "Tehtanja / meritve / ocene teleta", 0, 'C', 1,1, $lm, $x, true);
  }

   if (!empty($tl_masa) or !empty($tl_op) ){
  $x = $x + 5;
  $pdf->MultiCell(40, 0, "Rojstna masa: ".$tl_masa, 0, 'L', 0,1, $lm,$x , true);
  $pdf->MultiCell(40, 0, "Obseg prsi: ".$tl_op." cm", 0, 'L', 0,1, $lm+50,$x , true);
  $x = $x + 5;
  }

  $pdf->MultiCell(90, 0, "Označitev (O) ter Ocena in potrditev (P) teleta", 0, 'C', 1,1, $lm,$x , true);
  $pdf->MultiCell(90, 0, $tl_ozn, 0, 'L', 0,1, $lm,$x+5 , true);
  $pdf->MultiCell(90, 0, $tl_ozn2, 0, 'L', 0,1, $lm,$x+10 , true);
  $pdf -> SetFont('freesans', 'B', 10, '', true);
  $pdf->MultiCell(90, 0, "Podatki so posredovani na SIR!", 0, 'R', 0,1, '',$x+15 , true);
 // $pdf->Ln(4);

// set color for background
$pdf->SetFillColor(220, 255, 220);

$pdf->lastPage();

// ---------------------------------------------------------
// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf -> Output('tl_'.$v_id_zival.'.pdf', 'F');
$pdf -> Output('tet_listek.pdf', 'I');
// ============================================================+
// END OF FILE
// ============================================================+
