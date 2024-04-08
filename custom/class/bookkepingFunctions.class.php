<?php

require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';


require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/paymentdonation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/paymentloan.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';



class BookKeepingFunctions
{

    /**
     * Verifica que la factura exista dentro de diarios
     */
    public function cancelarPoliza($id)
    {
        // global $conf, $db;
        // $sql = " SELECT * FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping WHERE fk_doc =" . $id . " AND  doc_type = 'customer_invoice'";
        // echo $sql;
        // $cnclsql = $db->query($sql);
        // return $cnclsql;

        global $conf, $db;

        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping WHERE fk_doc = " . (int) $id . " AND doc_type = 'customer_invoice'";
        $cnclsql = $db->query($sql);
        if ($db->num_rows($cnclsql) > 0) {
            return 1;
        } else {
            return 0;
        }

    }

    /**
     * Si no existe una poliza previamente creada entonces almacena la hora de cancelación, y marca un checkbox como factura cancelada
     */

    public function cancelarDatosPoliza($id)
    {
        global $conf, $db;

        $hora_actual = date('Y-m-d');

        $ssql = "UPDATE " . MAIN_DB_PREFIX . "facture_extrafields SET date_cancel = '" . $hora_actual . "', fact_cancel = 1 WHERE fk_object = " . (int) $id;
        $resql = $db->query($ssql);

        if ($resql) {
            return 1;
        } else {
            return 0;
        }
    }



    // public function verificarCancelacion($id)
    // {
    //     global $conf, $db;

    //     $sql = " SELECT * FROM " . MAIN_DB_PREFIX . "facture_extrafields WHERE fk_object =" . $id . " AND  fact_cancel = 1  AND  uuid IS NOT NULL  ";
    //     $sqlres = $db->query($sql);
    //     return $sqlres;

    // }

    /**
     * Verifica que el checkbox este marcado, esto solo cuando se registra en contabilidad 
     */
    public function verificarCancelacion($id)
    {
        global $conf, $db;

        $sql = "SELECT COUNT(*) AS count_cancel FROM " . MAIN_DB_PREFIX . "facture_extrafields WHERE fk_object = " . (int) $id . " AND fact_cancel = 1 AND uuid IS NOT NULL";
        $sqlres = $db->query($sql);
        if ($sqlres) {
            $row = $db->fetch_array($sqlres);
            if ($row['count_cancel'] > 0) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }




    /**
     * Funcion para crear la respectiva poliza de facturas de cliente 
     */
    public function crearPoliza($object, $tabcompany = array(), $tabfac = array(), $tabttc = array(), $tabht = array(), $tabtva = array(), $tablocaltax1 = array(), $tablocaltax2 = array(), $def_tva = array(), $journal, $journal_label)
    {

        global $db, $conf, $langs, $user;

        $now = dol_now();
        $error = 0;

        $companystatic = new Societe($db);
        $invoicestatic = new Facture($db);
        $accountingaccountcustomer = new AccountingAccount($db);
        $accountingaccountcustomer->fetch(null, $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER, true);

        foreach ($tabfac as $key => $val) {		// Loop on each invoice

            $errorforline = 0;

            $totalcredit = 0;
            $totaldebit = 0;

            $db->begin();

            $companystatic->id = $tabcompany[$key]['id'];
            $companystatic->name = $tabcompany[$key]['name'];
            $companystatic->code_compta = $tabcompany[$key]['code_compta'];
            $companystatic->code_client = $tabcompany[$key]['code_client'];
            $companystatic->client = 3;

            $invoicestatic->id = $key;
            $invoicestatic->ref = (string) $val["ref"];
            $invoicestatic->type = $val["type"];
            $invoicestatic->close_code = $val["close_code"];

            $date = dol_print_date($val["date"], 'day');

            // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
            $replacedinvoice = 0;
            if ($invoicestatic->close_code == Facture::CLOSECODE_REPLACED) {
                $replacedinvoice = 1;
                $alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
                if ($alreadydispatched) {
                    $replacedinvoice = 2;
                }
            }

            // If not already into bookkeeping, we won't add it. If yes, do nothing (should not happen because creating replacement not possible if invoice is accounted)
            if ($replacedinvoice == 1) {
                $db->rollback();
                continue;
            }

            // Error if some lines are not binded/ready to be journalized
            if ($errorforinvoice[$key] == 'somelinesarenotbound') {
                $error++;
                $errorforline++;
                setEventMessages($langs->trans('ErrorInvoiceContainsLinesNotYetBounded', $val['ref']), null, 'errors');
            }

            // Thirdparty
            if (!$errorforline) {
                foreach ($tabttc[$key] as $k => $mt) {

                    $bookkeeping = new BookKeeping($db);

                    $bookkeeping->doc_date = $val["date"];
                    $bookkeeping->date_lim_reglement = $val["datereg"];
                    $bookkeeping->doc_ref = $val["ref"];
                    $bookkeeping->date_creation = $now;
                    $bookkeeping->doc_type = 'customer_invoice';
                    $bookkeeping->fk_doc = $key;
                    $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                    $bookkeeping->thirdparty_code = $companystatic->code_client;

                    $bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
                    $bookkeeping->subledger_label = $tabcompany[$key]['name'];

                    // $bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER; macv
                    $bookkeeping->numero_compte = $tabcompany[$key]['code_compta'];//$conf->global->ACCOUNTING_ACCOUNT_SUPPLIER;ehm
                    $bookkeeping->label_compte = $accountingaccountcustomer->label;

                    $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("SubledgerAccount");
                    $bookkeeping->montant = $mt;
                    $bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
                    $bookkeeping->debit = ($mt >= 0) ? $mt : 0;
                    $bookkeeping->credit = ($mt < 0) ? -$mt : 0;
                    $bookkeeping->code_journal = $journal;
                    $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                    $bookkeeping->fk_user_author = $user->id;
                    $bookkeeping->entity = $conf->entity;

                    $totaldebit += $bookkeeping->debit;
                    $totalcredit += $bookkeeping->credit;

                    $result = $bookkeeping->create($user);
                    if ($result < 0) {
                        if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
                            $error++;
                            $errorforline++;
                            $errorforinvoice[$key] = 'alreadyjournalized';
                            //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                        } else {
                            $error++;
                            $errorforline++;
                            $errorforinvoice[$key] = 'other';
                            setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                        }
                    } else {
                        if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && getDolGlobalInt('ACCOUNTING_ENABLE_AUTOLETTERING')) {
                            require_once DOL_DOCUMENT_ROOT . '/accountancy/class/lettering.class.php';
                            $lettering_static = new Lettering($db);

                            $nb_lettering = $lettering_static->bookkeepingLettering(array($bookkeeping->id));
                        }
                    }
                }
            }

            // Product / Service
            if (!$errorforline) {
                foreach ($tabht[$key] as $k => $mt) {

                    $accountingaccount = new AccountingAccount($db);
                    $resultfetch = $accountingaccount->fetch(null, $k, true);	// TODO Use a cache
                    $label_account = $accountingaccount->label;

                    // get compte id and label
                    if ($resultfetch > 0) {
                        $bookkeeping = new BookKeeping($db);
                        $bookkeeping->doc_date = $val["date"];
                        $bookkeeping->date_lim_reglement = $val["datereg"];
                        $bookkeeping->doc_ref = $val["ref"];
                        $bookkeeping->date_creation = $now;
                        $bookkeeping->doc_type = 'customer_invoice';
                        $bookkeeping->fk_doc = $key;
                        $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                        $bookkeeping->thirdparty_code = $companystatic->code_client;

                        if (!empty ($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT)) {
                            if ($k == getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT')) {
                                $bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
                                $bookkeeping->subledger_label = $tabcompany[$key]['name'];
                            } else {
                                $bookkeeping->subledger_account = '';
                                $bookkeeping->subledger_label = '';
                            }
                        } else {
                            $bookkeeping->subledger_account = '';
                            $bookkeeping->subledger_label = '';
                        }

                        $bookkeeping->numero_compte = $k;
                        $bookkeeping->label_compte = $label_account;

                        $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $label_account;
                        $bookkeeping->montant = $mt;
                        $bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
                        $bookkeeping->debit = ($mt < 0) ? -$mt : 0;
                        $bookkeeping->credit = ($mt >= 0) ? $mt : 0;
                        $bookkeeping->code_journal = $journal;
                        $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                        $bookkeeping->fk_user_author = $user->id;
                        $bookkeeping->entity = $conf->entity;

                        $totaldebit += $bookkeeping->debit;
                        $totalcredit += $bookkeeping->credit;

                        $result = $bookkeeping->create($user);
                        if ($result < 0) {
                            if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
                                $error++;
                                $errorforline++;
                                $errorforinvoice[$key] = 'alreadyjournalized';
                                //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                            } else {
                                $error++;
                                $errorforline++;
                                $errorforinvoice[$key] = 'other';
                                setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                            }
                        }
                    }
                }
            }

            // VAT
            if (!$errorforline) {
                $listoftax = array(0, 1, 2);
                foreach ($listoftax as $numtax) {
                    $arrayofvat = $tabtva;
                    if ($numtax == 1) {
                        $arrayofvat = $tablocaltax1;
                    }
                    if ($numtax == 2) {
                        $arrayofvat = $tablocaltax2;
                    }

                    foreach ($arrayofvat[$key] as $k => $mt) {
                        if ($mt) {

                            $accountingaccount = new AccountingAccount($db);
                            $accountingaccount->fetch(null, $k, true);	// TODO Use a cache for label
                            $label_account = $accountingaccount->label;

                            $bookkeeping = new BookKeeping($db);
                            $bookkeeping->doc_date = $val["date"];
                            $bookkeeping->date_lim_reglement = $val["datereg"];
                            $bookkeeping->doc_ref = $val["ref"];
                            $bookkeeping->date_creation = $now;
                            $bookkeeping->doc_type = 'customer_invoice';
                            $bookkeeping->fk_doc = $key;
                            $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                            $bookkeeping->thirdparty_code = $companystatic->code_client;

                            $bookkeeping->subledger_account = '';
                            $bookkeeping->subledger_label = '';

                            $bookkeeping->numero_compte = $k;
                            $bookkeeping->label_compte = $label_account;

                            $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("VAT") . ' ' . join(', ', $def_tva[$key][$k]) . ' %' . ($numtax ? ' - Localtax ' . $numtax : '');
                            $bookkeeping->montant = $mt;
                            $bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
                            $bookkeeping->debit = ($mt < 0) ? -$mt : 0;
                            $bookkeeping->credit = ($mt >= 0) ? $mt : 0;
                            $bookkeeping->code_journal = $journal;
                            $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                            $bookkeeping->fk_user_author = $user->id;
                            $bookkeeping->entity = $conf->entity;

                            $totaldebit += $bookkeeping->debit;
                            $totalcredit += $bookkeeping->credit;

                            $result = $bookkeeping->create($user);
                            if ($result < 0) {
                                if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'alreadyjournalized';
                                    //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                } else {
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'other';
                                    setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                }
                            }
                        }
                    }
                }
            }

            // Protection against a bug on lines before
            if (!$errorforline && (price2num($totaldebit, 'MT') != price2num($totalcredit, 'MT'))) {
                $error++;
                $errorforline++;
                $errorforinvoice[$key] = 'amountsnotbalanced';
                setEventMessages('Try to insert a non balanced transaction in book for ' . $invoicestatic->ref . '. Canceled. Surely a bug.', null, 'errors');
            }

            if (!$errorforline) {
                $db->commit();
            } else {
                $db->rollback();

                if ($error >= 10) {
                    setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped"), null, 'errors');
                    break; // Break in the foreach
                }
            }
        }

        $tabpay = $tabfac;

        if (empty ($error) && count($tabpay) > 0) {
            setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
        } elseif (count($tabpay) == $error) {
            setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
        } else {
            setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
        }

        $action = '';

    }


    /*
    *Obtiene los respectivos valores para crear la poliza de cancelación de facturas
    */
    public function getCancelParameters($object, $idPoliza)
    {

        global $db, $conf;
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
                END AS typeline, ab.date_cancel";
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
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_extrafields ab ON  ab.fk_object = f.rowid ";
        $sql .= " WHERE  f.rowid=" . $idPoliza;
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
                    $tabfac[$obj->rowid]["date_cancel"] = $obj->date_cancel;//CC

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

        $this->crearCancelacion($object, $tabfac, $tabcompany, $tabttc, $tabht, $tabtva, $tablocaltax1, $def_tva);


    }



    /**
     * Crea la cancelación de poliza de facturas
     */
    public function crearCancelacion($object, $tabfac = array(), $tabcompany = array(), $tabttc = array(), $tabht = array(), $tabtva = array(), $tablocaltax1 = array(), $tablocaltax2 = array(), $def_tva = array())
    {
        global $db, $conf, $langs, $user;


        foreach ($tabfac as $key => $val) {
            $dateCancel = $val['date_cancel'];
            $type = $val['type'];
        }


        $datee = $db->jdate($dateCancel);

        $now = dol_now();
        $error = 0;

        $companystatic = new Societe($db);
        $invoicestatic = new Facture($db);
        $accountingaccountcustomer = new AccountingAccount($db);
        $accountingaccountcustomer->fetch(null, $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER, true);

        foreach ($tabfac as $key => $val) {		// Loop on each all invoice lines and lines from bookkeeping
            $errorforline = 0;
            $totalcredit = 0;
            $totaldebit = 0;

            $db->begin();
            //agregado encabezado de poliza
            $companystatic->id = $tabcompany[$key]['id'];
            $companystatic->name = $tabcompany[$key]['name'];
            $companystatic->code_compta = $tabcompany[$key]['code_compta'];
            $companystatic->code_compta_fournisseur = $tabcompany[$key]['code_compta_fournisseur'];
            $companystatic->code_client = $tabcompany[$key]['code_client'];
            $companystatic->code_fournisseur = $tabcompany[$key]['code_fournisseur'];
            $companystatic->client = 3;

            $invoicestatic->id = $key;
            $invoicestatic->ref = (string) $val["ref"];
            $invoicestatic->type = $val["type"];
            $invoicestatic->close_code = $val["close_code"];
            $date = dol_print_date($val["date"], 'day');

            // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
            $replacedinvoice = 0;
            if ($invoicestatic->close_code == Facture::CLOSECODE_REPLACED) {
                $replacedinvoice = 1;
                $alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
                if ($alreadydispatched) {
                    $replacedinvoice = 2;
                }
            }

            // If not already into bookkeeping, we won't add it. If yes, do nothing (should not happen because creating replacement not possible if invoice is accounted)
            if ($replacedinvoice == 1) {
                $db->rollback();
                continue;
            }

            // Error if some lines are not binded/ready to be journalized
            if ($errorforinvoice[$key] == 'somelinesarenotbound') {
                $error++;
                $errorforline++;
                setEventMessages($langs->trans('ErrorInvoiceContainsLinesNotYetBounded', $val['ref']), null, 'errors');
            }


            // Thirdparty
            if (!$errorforline) {
                foreach ($tabttc[$key] as $k => $mt) { // agrenado linea de poliza nativa

                    $bookkeeping = new BookKeeping($db);
                    $bookkeeping->doc_date = $db->escape($datee);
                    $bookkeeping->date_lim_reglement = $val["datereg"];
                    $bookkeeping->doc_ref = $val["ref"] . "-C";
                    $bookkeeping->date_creation = $now;
                    $bookkeeping->doc_type = 'customer_invoice';
                    $bookkeeping->fk_doc = $key;
                    $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                    $bookkeeping->thirdparty_code = $companystatic->code_client;

                    $bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
                    $bookkeeping->subledger_label = $tabcompany[$key]['name'];

                    $bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
                    $bookkeeping->label_compte = $accountingaccountcustomer->label;

                    //CC
                    $CD = ($mt < 0) ? 'Ret.' : '';

                    $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . '-C' . ' - ' . $langs->trans("SubledgerAccount") . $CD;
                    $bookkeeping->montant = -$mt;
                    $bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
                    $bookkeeping->debit = ($mt >= 0) ? -$mt : 0;
                    $bookkeeping->credit = ($mt < 0) ? $mt : 0;
                    $bookkeeping->code_journal = 'DC';
                    $bookkeeping->journal_label = $langs->transnoentities('Diario de Cancelación');
                    $bookkeeping->fk_user_author = $user->id;
                    $bookkeeping->entity = $conf->entity;


                    $result = $bookkeeping->create($user);
                    if ($result < 0) {
                        if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
                            $error++;
                            $errorforline++;
                            $errorforinvoice[$key] = 'alreadyjournalized';
                            //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                        } else {
                            $error++;
                            $errorforline++;
                            $errorforinvoice[$key] = 'other';
                            setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                        }
                    }
                }
            }

            // Product / Service
            if (!$errorforline) {

                foreach ($tabht as $k => $value2) { //agregando a poliza lineas agrupadas por unidades de negocio

    

                    $accountingaccount = new AccountingAccount($db);
                    $resultfetch = $accountingaccount->fetch(null, $value2->compte, true);	// TODO Use a cache
                    $label_account = $accountingaccount->label;

                    // get compte id and label
                    if ($resultfetch > 0) {
                        $bookkeeping = new BookKeeping($db);
                        $bookkeeping->doc_date = $db->escape($datee);
                        $bookkeeping->date_lim_reglement = $val["datereg"];
                        $bookkeeping->doc_ref = $value2->ref . "-C";
                        $bookkeeping->date_creation = $now;
                        $bookkeeping->doc_type = 'customer_invoice';
                        $bookkeeping->fk_doc = $key;
                        $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                        $bookkeeping->thirdparty_code = $value2->code_client;

                        $bookkeeping->subledger_account = '';
                        $bookkeeping->subledger_label = '';

                        $bookkeeping->numero_compte = $accountingaccount->account_number;
                        $bookkeeping->label_compte = $accountingaccount->label;

                        //CC
                        $CD = ($mt < 0) ? 'Ret.' : '';

                        if( $type == 2){

                        $bookkeeping->label_operation = dol_trunc($value2->name, 16) . ' - ' . $value2->ref . '-C' . ' - ' . $accountingaccount->label . $CD;
                        //$bookkeeping->montant = -$value2->total_ht;
                       // $bookkeeping->sens = ($mt >= 0) ? 'C' : 'D';
                        //$bookkeeping->credit = -$value2->total_ht;
                        $bookkeeping->debit = $value2->total_ht;
                        $bookkeeping->code_journal = 'DC';
                        $bookkeeping->journal_label = $langs->transnoentities('Diario de Cancelación');
                        $bookkeeping->fk_user_author = $user->id;
                        $bookkeeping->entity = $conf->entity;
                        }else{
                        $bookkeeping->label_operation = dol_trunc($value2->name, 16) . ' - ' . $value2->ref . '-C' . ' - ' . $accountingaccount->label . $CD;
                        $bookkeeping->montant = -$value2->total_ht;
                        $bookkeeping->sens = ($mt >= 0) ? 'C' : 'D';
                        $bookkeeping->credit = -$value2->total_ht;
                        $bookkeeping->debit = 0;
                        $bookkeeping->code_journal = 'DC';
                        $bookkeeping->journal_label = $langs->transnoentities('Diario de Cancelación');
                        $bookkeeping->fk_user_author = $user->id;
                        $bookkeeping->entity = $conf->entity;
                        }

                        $result = $bookkeeping->create($user);

                        if ($result < 0) {
                            if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
                                $error++;
                                $errorforline++;
                                $errorforinvoice[$key] = 'alreadyjournalized';
                                //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                            } else {
                                $error++;
                                $errorforline++;
                                $errorforinvoice[$key] = 'other';
                                setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                            }
                        }
                    }
                }
            }

            // VAT
            if (!$errorforline) {
                $listoftax = array(0, 1, 2);
                foreach ($listoftax as $numtax) { // Inserta los tres tipos de iva existentes en caso de contar con ellos 
                    $arrayofvat = $tabtva;
                    $tva_label = '_IVA 16';
                    if ($numtax == 1) {
                        $arrayofvat = $tablocaltax1;
                        $tva_label = '_Localtax 1';
                    }
                    if ($numtax == 2) {
                        $arrayofvat = $tablocaltax2;
                        $tva_label = '_Localtax 2';
                    }

                    foreach ($arrayofvat[$key] as $k => $mt) { // agrega los Impuestos a la poliza 
                        $accountingaccount = new AccountingAccount($db);

                        if ($k) {

                            $resultt = $accountingaccount->fetch('', $k, true);	// MACV 25/01/2024
                            if ($result < 1) { // MACV 25/01/2024
                                $resultt = $accountingaccount->fetch($k, '', true);	// MACV 25/01/2024
                            }

                            $bookkeeping = new BookKeeping($db);
                            $bookkeeping->doc_date = $db->escape($datee);
                            $bookkeeping->date_lim_reglement = $val["datereg"];
                            $bookkeeping->doc_ref = $val["ref"] . "-C";
                            $bookkeeping->date_creation = $now;
                            $bookkeeping->doc_type = 'customer_invoice';
                            $bookkeeping->fk_doc = $key;
                            $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                            $bookkeeping->thirdparty_code = $companystatic->code_client;

                            $bookkeeping->subledger_account = '';
                            $bookkeeping->subledger_label = '';
                            $bookkeeping->numero_compte = $accountingaccount->account_number;
                            $bookkeeping->label_compte = $accountingaccount->label;

                            //CC
                            $CD = ($mt < 0) ? 'Ret.' : '';

                            $bookkeeping->label_operation = 'C ' . ' - ' . $def_tva[$key][$k . $tva_label];
                            $bookkeeping->montant = -$mt;
                            $bookkeeping->sens = ($mt >= 0) ? 'C' : 'D';
                            $bookkeeping->credit = ($mt >= 0) ? (-$mt) > 0 ? $mt : -$mt : 0;
                            $bookkeeping->debit = ($mt < 0) ? (-$mt) > 0 ? $mt : $mt : 0;
                            $bookkeeping->code_journal = 'DC';
                            $bookkeeping->journal_label = $langs->transnoentities('Diario de Cancelación');
                            $bookkeeping->fk_user_author = $user->id;
                            $bookkeeping->entity = $conf->entity;
                            $result = $bookkeeping->create($user);
                            if ($result < 0) {
                                if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'alreadyjournalized';
                                } else {
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'other';
                                    setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                }
                            }
                        }
                    }
                }
            }

            //     if (!$errorforline) { // si no exciste error, se cierra la factura
            //         $close_code = GETPOST("close_code", 'restricthtml');
            //         $close_note = GETPOST("close_note", 'restricthtml');
            //         if ($close_code) {
            //             require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
            //             $facture = new Facture ($db);
            //             $result = $facture->setCanceled($user, $close_code, $close_note);
            //             if ($result < 0) {
            //                 setEventMessages($object->error, $object->errors, 'errors');
            //             } else {
            //                 $db->commit();
            //                 setEventMessages($langs->trans("Se creo poliza"), null, 'mesgs');
            //             }
            //         } else {
            //             $db->rollback();
            //             setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Reason")), null, 'errors');
            //         }
            //         //$db->commit();

            //     } else {
            //         $db->rollback();
            //         setEventMessages($langs->trans("Ya existe la poliza de cancelación"), null, 'errors');
            //         if ($error >= 10) {
            //             setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped"), null, 'errors');
            //             break; // Break in the foreach
            //         }
            //     }
            // }

            if (!$errorforline) {
                $db->commit();
                setEventMessages($langs->trans("Se creo la poliza"), null, 'mesgs');
            } else {
                $db->rollback();
                setEventMessages($langs->trans("Ya existe la poliza de cancelación"), null, 'errors');
                if ($error >= 10) {
                    setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped"), null, 'errors');
                    break; // Break in the foreach
                }
            }
            $action = '';
           
        }
      // $action  = '';
     //   header("Location: " . $_SERVER['PHP_SELF']);
    //   //  exit;

    }


     


}




