<?php

require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/class/bookkepingFunctions.php';




/** Obtiene  la poliza a cancelar, ademas de obtener las lineas de la factrua para separar por unidades de negocio */
$sql = "SELECT f.rowid, f.ref, f.type, f.datef as df,  f.date_lim_reglement as dlr, f.close_code,";
$sql .= "  SUM(fd.total_ht) as total_ht ,  SUM(fd.total_tva) as total_tva , SUM(fd.total_ttc) as total_ttc, ";
$sql .= "  s.nom as name, s.code_client, s.code_fournisseur,";
if (!empty ($conf->global->MAIN_COMPANY_PERENTITY_SHARED)) {
    $sql .= " spe.accountancy_code_customer as code_compta,";
    $sql .= " spe.accountancy_code_supplier as code_compta_fournisseur,";
} else {
    $sql .= " s.code_compta as code_compta,";
    $sql .= " s.code_compta_fournisseur,";
}
$sql .= "  aa.account_number as compte2, aa.label as label_compte2,";
$sql .= " d.numero_compte as compte,d.label_operation,d.label_compte,d.montant ";
$sql .= " ,CASE
                WHEN label_operation LIKE '%Localtax 1%' THEN 'Localtax 1'
                WHEN label_operation LIKE '%Localtax 2%' THEN 'Localtax 2'
                WHEN label_operation LIKE '%IVA 16%' THEN 'IVA 16'
                WHEN label_operation not LIKE '%IVA 16%' and sens = 'D' THEN 'cuenta'
                WHEN label_operation not LIKE '%IVA 16%' and sens = 'C' THEN 'line'
                ELSE 'Otro'
                END AS typeline";
$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = fd.fk_product";
if (!empty ($conf->global->MAIN_PRODUCT_PERENTITY_SHARED)) {
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
}
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = fd.fk_code_ventilation";
$sql .= " JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = fd.fk_facture";
$sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
if (!empty ($conf->global->MAIN_COMPANY_PERENTITY_SHARED)) {
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = " . ((int) $conf->entity);
}
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facturedet_extrafields c ON c.fk_object = fd.rowid";
$sql .= " inner join " . MAIN_DB_PREFIX . "accounting_bookkeeping d ON	d.fk_doc = f.rowid and d.doc_type = 'customer_invoice'";
$sql .= " WHERE  f.rowid=" . $object->id;
$sql .= "  GROUP BY aa.label, aa.account_number,d.numero_compte,d.label_operation,sens,d.label_compte,d.montant";

$result = $db->query($sql);
if ($result) {
    $tabfac = array();
    $tabht = array();
    $tabtva = array();
    $def_tva = array();
    $tabttc = array();
    $tablocaltax1 = array();
    $tablocaltax2 = array();
    $tabcompany = array();
    $num = $db->num_rows($result);

    // Variables
    $cptcli = (($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER != "")) ? $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER : 'NotDefined';
    $cpttva = (!empty ($conf->global->ACCOUNTING_VAT_SOLD_ACCOUNT)) ? $conf->global->ACCOUNTING_VAT_SOLD_ACCOUNT : 'NotDefined';

    $i = 0;

    while ($obj = $db->fetch_object($result)) {

        // Controls
        $compta_soc = (!empty ($obj->code_compta)) ? $obj->code_compta : $cptcli;

        $compta_prod = $obj->compte;
        //Si la cuenta contable es vacia, asigna la que es por defecto 
        if (empty ($compta_prod)) {
            if ($obj->product_type == 0) {
                $compta_prod = (!empty ($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT)) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : 'NotDefined';
            } else {
                $compta_prod = (!empty ($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT)) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : 'NotDefined';
            }
        }
        // si la linea tiene el iva 16 
        if ($obj->typeline == 'IVA 16') {
            $vatdata = getTaxesFromId($obj->tva_tx . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : ''), '', '', 0);
            $compta_tva = (!empty ($obj->compte) ? $obj->compte : $cpttva);//macv 012/02/2024
            // Si la entrada no está definida, asigna el valor del objeto $obj->montant a esa posición.

            if (!isset ($tabtva[$obj->rowid][$compta_tva])) {
                $tabtva[$obj->rowid][$compta_tva] = $obj->montant;
            }

            //define la etiqueta del impuesto para ingresarlo en label_operation
            $def_tva[$obj->rowid][$compta_tva . '_' . $obj->typeline] = ' (' . $obj->label_operation . ')';

        } elseif ($obj->typeline == 'Localtax 1') {
            // si la linea tiene  Impuesto 1
            $compta_tva = (!empty ($obj->compte) ? $obj->compte : $cpttva);//macv 012/02/2024
            $vatdata = getTaxesFromId($obj->tva_tx . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : ''), '', '', 0);
            $compta_localtax1 = (!empty ($obj->compte) ? $obj->compte : $cpttva);

            // Si la entrada no está definida, asigna el valor del objeto $obj->montant a esa posición.
            if (!isset ($tablocaltax1[$obj->rowid][$compta_localtax1])) {
                $tablocaltax1[$obj->rowid][$compta_localtax1] = $obj->montant;
            }

            //define la etiqueta del impuesto para ingresarlo en label_operation
            $def_tva[$obj->rowid][$compta_tva . '_' . $obj->typeline] = ' (' . $obj->label_operation . ')';
        } elseif ($obj->typeline == 'Localtax 2') {
            // si la linea tiene impuesto 2
            $compta_tva = (!empty ($obj->compte) ? $obj->compte : $cpttva);//macv 012/02/2024
            $vatdata = getTaxesFromId($obj->tva_tx . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : ''), '', '', 0);
            $compta_localtax2 = (!empty ($obj->compte) ? $obj->compte : $cpttva);

            // Si la entrada no está definida, asigna el valor del objeto $obj->montant a esa posición.
            if (!isset ($tablocaltax2[$obj->rowid][$compta_localtax2])) {
                $tablocaltax2[$obj->rowid][$compta_localtax2] = $obj->montant;
            }

            //define la etiqueta del impuesto para ingresarlo en label_operation
            $def_tva[$obj->rowid][$compta_tva . '_' . $obj->typeline] = ' (' . $obj->label_operation . ')';
        }

        $line = new FactureLigne($db);
        $line->fetch($obj->fdid);

        // Situation invoices handling
        $prev_progress = $line->get_prev_progress($obj->rowid);

        if ($obj->type == Facture::TYPE_SITUATION) {
            if ($obj->situation_percent == 0) {
                $situation_ratio = 0;
            } else {
                $situation_ratio = ($obj->situation_percent - $prev_progress) / $obj->situation_percent;
            }
        } else {
            $situation_ratio = 1;
        }

        //si la linea tiene unidades de negocio
        if ($obj->typeline == 'line') {
            $tabht[] = $obj;
            $compta_prod = $obj->compte;
            $tabht[$obj->rowid][$compta_prod] = $obj->montent;
            if (empty ($line->tva_npr)) {
                $tabtva[$obj->rowid][$compta_tva] += $obj->total_tva * $situation_ratio;
            }
            // Invoice lines
            $tabfac[$obj->rowid]["date"] = $db->jdate($obj->df);
            $tabfac[$obj->rowid]["datereg"] = $db->jdate($obj->dlr);
            $tabfac[$obj->rowid]["ref"] = $obj->ref;
            $tabfac[$obj->rowid]["type"] = $obj->type;
            $tabfac[$obj->rowid]["description"] = $obj->label_compte;
            $tabfac[$obj->rowid]["close_code"] = $obj->close_code;
        }


        // // Avoid warnings
        // if (!isset($tabttc[$obj->rowid][$compta_soc])) {
        //     $tabttc[$obj->rowid][$compta_soc] = 0;
        // }
        //si la linea es el asiento contable en debe
        if ($obj->typeline == 'cuenta') {
            $tabttc[$obj->rowid][$obj->compte] = $obj->montant;
        }

        $tabcompany[$obj->rowid] = array(
            'id' => $obj->socid,
            'name' => $obj->name,
            'code_client' => $obj->code_client,
            'code_compta' => $compta_soc
        );

        $i++;
    }
} else {
    dol_print_error($db);
}
$errorforinvoice = array();

// Loop in invoices to detect lines with not binding lines
foreach ($tabfac as $key => $val) {		// Loop on each invoice
    $sql = "SELECT COUNT(fd.rowid) as nb";
    $sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd";
    $sql .= " WHERE fd.product_type <= 2 AND fd.fk_code_ventilation <= 0";
    $sql .= " AND fd.total_ttc <> 0 AND fk_facture = " . ((int) $key);
    // echo $sql;
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj->nb > 0) {
            $errorforinvoice[$key] = 'somelinesarenotbound';
        }
    } else {
        dol_print_error($db);
    }

}



//CC
$accountingaccount = new AccountingAccount($db);

// Get informations of journal
$accountingjournalstatic = new AccountingJournal($db);
$accountingjournalstatic->fetch($id_journal);
$journal = $accountingjournalstatic->code;
$journal_label = $accountingjournalstatic->label;


$poliza = new BookKeepingFunctions($db);

if ($action == "createpoliza" && $confirm == 'yes') {

    $object->fetch($id);
		$close_code = GETPOST("close_code", 'restricthtml');
		$close_note = GETPOST("close_note", 'restricthtml');
		if ($close_code) {
			$result = $object->setCanceled($user, $close_code, $close_note);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Reason")), null, 'errors');
		}

    $resultado = $poliza->cancelarPoliza(GETPOST('facid'));
    if ($resultado > 0) {
        $poliza->crearCancelacion($object, $tabfac, $tabcompany, $tabttc, $tabht, $tabtva, $tablocaltax1, $def_tva);
    } else {
        $poliza->cancelarDatosPoliza(GETPOST('facid'));
    }
} //CC


function transformPolizaPay($object, $date)
{
    global $conf, $db;
    // var_dump($object->id);
    // exit;

    $error = 0;

    $db->begin();

    echo $refsql = "SELECT a.ref, e.piece_num 
            from llx_facture a 
            inner join llx_paiement_facture b on a.rowid = b.fk_facture 
            inner join llx_paiement  c ON b.fk_paiement = c.rowid
            inner join llx_bank_url d on c.rowid = d.url_id and d.type = 'payment'
            inner join llx_accounting_bookkeeping e ON e.fk_doc = d.fk_bank and doc_type = 'bank'
            where c.rowid = " . $object->id . " GROUP BY a.ref, e.piece_num ";
    $reffsql = $db->query($refsql);
    // exit;
    if ($reffsql) {
        while ($ref_fac = $db->fetch_object($reffsql)) {
            // Select data from the original entries based on doc_ref
            $sql = ' SELECT doc_date, doc_type, doc_ref, fk_doc, fk_docdet, thirdparty_code, subledger_account, subledger_label,';
            $sql .= ' numero_compte, label_compte, label_operation, debit, credit, montant, sens, fk_user_author,';
            $sql .= ' import_key, code_journal, journal_label';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'accounting_bookkeeping WHERE doc_ref LIKE "%' . $ref_fac->ref . '%"';
            $sql .= ' and piece_num = ' . $ref_fac->piece_num;
          //  echo $sql;
            $resql = $db->query($sql);
            // exit;
            if ($resql) {
                echo 'aaaaaaaa';
                // Obtener el último piece_num
                $sqlMaxPieceNum = 'SELECT MAX(piece_num) AS max_piece_num FROM ' . MAIN_DB_PREFIX . 'accounting_bookkeeping';
                $resMaxPieceNum = $db->query($sqlMaxPieceNum);
                $maxPieceNum = $db->fetch_object($resMaxPieceNum)->max_piece_num;
                $newPieceNum = $maxPieceNum + 1;
                while ($objres = $db->fetch_object($resql)) {
                    list($dia, $mes, $anio) = explode('/', $date);
                    $newDate = $anio . '-' . $mes . '-' . $dia;

                    $sqlcrt = 'INSERT INTO ' . MAIN_DB_PREFIX . 'accounting_bookkeeping (doc_date, doc_type, doc_ref, fk_doc, fk_docdet,';
                    $sqlcrt .= ' thirdparty_code, subledger_account, subledger_label, numero_compte, label_compte,';
                    $sqlcrt .= ' label_operation, debit, credit, montant, sens, fk_user_author, import_key, code_journal,';
                    $sqlcrt .= ' journal_label, piece_num) VALUES (';
                    $sqlcrt .= "'" . $db->escape($newDate) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->doc_type) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->doc_ref) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->fk_doc) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->fk_docdet) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->thirdparty_code) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->subledger_account) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->subledger_label) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->numero_compte) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->label_compte) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->label_operation) . "', ";
                    $sqlcrt .= "'" . $db->escape(-$objres->debit) . "', ";
                    $sqlcrt .= "'" . $db->escape(-$objres->credit) . "', ";
                    $sqlcrt .= "'" . $db->escape(-$objres->montant) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->sens) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->fk_user_author) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->import_key) . "', ";
                    $sqlcrt .= "'DC', ";
                    $sqlcrt .= "'Diario de Cancelación', ";
                    $sqlcrt .= "'" . $db->escape($newPieceNum) . "'";
                    $sqlcrt .= ')';
                    // echo $sqlcrt;
                    $resinsert = $db->query($sqlcrt);

                    if (!$resinsert) {
                        $error = 1;
                    }

                }
                $sqls = " select fk_facture  FROM  llx_paiement_facture
                        WHERE fk_paiement = " . $object->id;
                $resqls = $db->query($sqls);
                $facture = $db->fetch_object($resqls)->fk_facture;

                $sqll = "UPDATE llx_facture SET paye = 0, fk_statut = 1
                WHERE rowid = " . $facture;
                $resqll = $db->query($sqll);

                $ssql = "UPDATE llx_paiement_facture SET fk_facture = null  WHERE fk_paiement = " . $object->id;
                $resql = $db->query($ssql);

            } else {
                $error = 1;
            }

            if (!$error) {

                $db->commit();
                return 1;
            } else {

                $db->rollback();
                return -1;
            }

        }
    }


}

function transformPolizaPayFourn($object, $date)
{
    global $conf, $db;
    // var_dump($object->id);
// exit;
    $error = 0;

    $db->begin();

    echo $refsql = "select a.ref, e.piece_num from llx_facture_fourn a 
            inner join llx_paiementfourn_facturefourn b on a.rowid = b.fk_facturefourn
            inner join llx_paiementfourn  c ON b.fk_paiementfourn = c.rowid
            inner join llx_bank_url d on c.rowid = d.url_id and d.type = 'payment_supplier'
            inner join llx_accounting_bookkeeping e ON e.fk_doc = d.fk_bank and doc_type = 'bank'
            where c.rowid = " . $object->id . " GROUP BY a.ref, e.piece_num ";
    $reffsql = $db->query($refsql);
    //exit;
    if ($reffsql) {
        while ($ref_fac = $db->fetch_object($reffsql)) {
            // Select data from the original entries based on doc_ref
            $sql = ' SELECT doc_date, doc_type, doc_ref, fk_doc, fk_docdet, thirdparty_code, subledger_account, subledger_label,';
            $sql .= ' numero_compte, label_compte, label_operation, debit, credit, montant, sens, fk_user_author,';
            $sql .= ' import_key, code_journal, journal_label';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'accounting_bookkeeping WHERE doc_ref LIKE "%' . $ref_fac->ref . '%"';
            $sql .= ' and piece_num = ' . $ref_fac->piece_num;

            $resql = $db->query($sql);

            if ($resql) {

                // Obtener el último piece_num
                $sqlMaxPieceNum = 'SELECT MAX(piece_num) AS max_piece_num FROM ' . MAIN_DB_PREFIX . 'accounting_bookkeeping';
                $resMaxPieceNum = $db->query($sqlMaxPieceNum);
                $maxPieceNum = $db->fetch_object($resMaxPieceNum)->max_piece_num;
                $newPieceNum = $maxPieceNum + 1;
                while ($objres = $db->fetch_object($resql)) {
                    list($dia, $mes, $anio) = explode('/', $date);
                    $newDate = $anio . '-' . $mes . '-' . $dia;

                    $sqlcrt = 'INSERT INTO ' . MAIN_DB_PREFIX . 'accounting_bookkeeping (doc_date, doc_type, doc_ref, fk_doc, fk_docdet,';
                    $sqlcrt .= ' thirdparty_code, subledger_account, subledger_label, numero_compte, label_compte,';
                    $sqlcrt .= ' label_operation, debit, credit, montant, sens, fk_user_author, import_key, code_journal,';
                    $sqlcrt .= ' journal_label, piece_num) VALUES (';
                    $sqlcrt .= "'" . $db->escape($newDate) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->doc_type) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->doc_ref) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->fk_doc) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->fk_docdet) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->thirdparty_code) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->subledger_account) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->subledger_label) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->numero_compte) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->label_compte) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->label_operation) . "', ";
                    $sqlcrt .= "'" . $db->escape(-$objres->debit) . "', ";
                    $sqlcrt .= "'" . $db->escape(-$objres->credit) . "', ";
                    $sqlcrt .= "'" . $db->escape(-$objres->montant) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->sens) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->fk_user_author) . "', ";
                    $sqlcrt .= "'" . $db->escape($objres->import_key) . "', ";
                    $sqlcrt .= "'DC', ";
                    $sqlcrt .= "'Diario de Cancelación', ";
                    $sqlcrt .= "'" . $db->escape($newPieceNum) . "'";
                    $sqlcrt .= ')';
                    // echo $sqlcrt;
                    $resinsert = $db->query($sqlcrt);

                    if (!$resinsert) {
                        $error = 1;
                    }

                }
                $sqls = " select fk_facturefourn  FROM  llx_paiementfourn_facturefourn
                        WHERE fk_paiementfourn = " . $object->id;
                $resqls = $db->query($sqls);
                $facture = $db->fetch_object($resqls)->fk_facturefourn;

                $sqll = "UPDATE llx_facture_fourn SET paye = 0, fk_statut = 1
                WHERE rowid = " . $facture;
                $resqll = $db->query($sqll);

                $ssql = "UPDATE llx_paiementfourn_facturefourn SET fk_facturefourn = null  WHERE fk_paiementfourn = " . $object->id;
                $resql = $db->query($ssql);

            } else {
                $error = 1;
            }

            if (!$error) {

                $db->commit();
                return 1;
            } else {

                $db->rollback();
                return -1;
            }

        }
    }


}


