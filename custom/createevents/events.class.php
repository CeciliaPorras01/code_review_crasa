<?php

require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";

//CC
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
//CC


class events extends CommonObject {

    /**
	 * Add message into database
	 *
	 * @param User 	 $user      		  User that creates
	 * @param int  	 $notrigger 		  0=launch triggers after, 1=disable triggers
	 * @param array	 $filename_list       List of files to attach (full path of filename on file system)
	 * @param array	 $mimetype_list       List of MIME type of attached files
	 * @param array	 $mimefilename_list   List of attached file name in message
	 * @return int						  <0 if KO, >0 if OK
	 */
	public function createActionPaiement($user, $object,  $notrigger = 0)
	{
		global $conf, $langs, $db;

		$error = 0;

		$now = dol_now();

		// Clean parameters
		if (isset($this->fk_track_id)) {
			$this->fk_track_id = trim($this->fk_track_id);
		}

		if (isset($this->message)) {
			$this->message = trim($this->message);
		}
		$db->begin();
		include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($db);
		$actioncomm->type_code = 'AC_OTH_AUTO';
		$actioncomm->code = 'PAIEMENT_CREATE_CFDI';
		if ($this->private) {
			$actioncomm->code = 'PAIEMENT_CREATE_CFDI_PRIVATE';
		}
		$actioncomm->socid = '';
		$actioncomm->fk_project = $object->fk_projet;
		$actioncomm->label = "CFDI generado " ;
		$actioncomm->note_private = 'Autor: ' . $user->firstname . ' ' . $user->lastname;
		$actioncomm->userassigned = array($user->id);
		$actioncomm->userownerid = $user->id;
		$actioncomm->datep = $now;
		$actioncomm->datef = $now;
		$actioncomm->percentage = -1; // percentage is not relevant for punctual events
		$actioncomm->elementtype = 'paiement';
		$actioncomm->fk_element = $object->id;

		$attachedfiles = array();
		$attachedfiles['paths'] = $filename_list;
		$attachedfiles['names'] = $mimefilename_list;
		$attachedfiles['mimes'] = $mimetype_list;
		if (is_array($attachedfiles) && count($attachedfiles) > 0) {
			$actioncomm->attachedfiles = $attachedfiles;
		}

		if (!empty($mimefilename_list) && is_array($mimefilename_list)) {
			$actioncomm->note_private = dol_concatdesc($actioncomm->note_private, "\n".$langs->transnoentities("AttachedFiles").': '.join(';', $mimefilename_list));
		}

		$actionid = $actioncomm->create($user);
		if ($actionid <= 0) {
			$error++;
			$this->error = $actioncomm->error;
			$this->errors = $actioncomm->errors;
		}

		// Commit or rollback
		if ($error) {
			$db->rollback();
			return -1 * $error;
		} else {
			$db->commit();
			return 1;
		}
	}

	public function createActionPaiementTimbre($user, $object,  $notrigger = 0)
	{
		global $conf, $langs, $db;

		$error = 0;

		$now = dol_now();

		// Clean parameters
		if (isset($this->fk_track_id)) {
			$this->fk_track_id = trim($this->fk_track_id);
		}

		if (isset($this->message)) {
			$this->message = trim($this->message);
		}

		$db->begin();
		include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($db);
		$actioncomm->type_code = 'AC_OTH_AUTO';
		$actioncomm->code = 'PAIEMENT_CREATE_CFDI_TIMBRE';
		if ($this->private) {
			$actioncomm->code = 'PAIEMENT_CREATE_CFDI_TIMBRE_PRIVATE';
		}
		$actioncomm->socid = '';
		$actioncomm->fk_project = $object->fk_projet;
		$actioncomm->label = "CFDI Timbrado " ;
		$actioncomm->note_private = 'Autor: ' . $user->firstname . ' ' . $user->lastname;
		$actioncomm->userassigned = array($user->id);
		$actioncomm->userownerid = $user->id;
		$actioncomm->datep = $now;
		$actioncomm->datef = $now;
		$actioncomm->percentage = -1; // percentage is not relevant for punctual events
		$actioncomm->elementtype = 'paiement';
		$actioncomm->fk_element = $object->id;

		$attachedfiles = array();
		$attachedfiles['paths'] = $filename_list;
		$attachedfiles['names'] = $mimefilename_list;
		$attachedfiles['mimes'] = $mimetype_list;
		if (is_array($attachedfiles) && count($attachedfiles) > 0) {
			$actioncomm->attachedfiles = $attachedfiles;
		}

		if (!empty($mimefilename_list) && is_array($mimefilename_list)) {
			$actioncomm->note_private = dol_concatdesc($actioncomm->note_private, "\n".$langs->transnoentities("AttachedFiles").': '.join(';', $mimefilename_list));
		}

		$actionid = $actioncomm->create($user);
		if ($actionid <= 0) {
			$error++;
			$this->error = $actioncomm->error;
			$this->errors = $actioncomm->errors;
		}

		// Commit or rollback
		if ($error) {
			$db->rollback();
			return -1 * $error;
		} else {
			$db->commit();
			return 1;
		}
	}

	public function createActionPaiementCreate($user, $object,  $notrigger = 0)
	{
		global $conf, $langs, $db;
		$error = 0;

		$now = dol_now();

		// Clean parameters
		if (isset($this->fk_track_id)) {
			$this->fk_track_id = trim($this->fk_track_id);
		}

		if (isset($this->message)) {
			$this->message = trim($this->message);
		}

		$db->begin();
		include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($db);
		$actioncomm->type_code = 'AC_OTH_AUTO';
		$actioncomm->code = 'CREATE_PAIEMENT';
		if ($this->private) {
			$actioncomm->code = 'CREATE_PAIEMENT_PRIVATE';
		}
		$actioncomm->socid = '';
		$actioncomm->fk_project = $object->fk_projet;
		$actioncomm->label = "Pago creado ";
		$actioncomm->note_private = 'Autor: ' . $user->firstname . ' ' . $user->lastname;
		$actioncomm->userassigned = array($user->id);
		$actioncomm->userownerid = $user->id;
		$actioncomm->datep = $now;
		$actioncomm->datef = $now;
		$actioncomm->percentage = -1; // percentage is not relevant for punctual events
		$actioncomm->elementtype = 'paiement';
		$actioncomm->fk_element = $object->id;

		$attachedfiles = array();
		$attachedfiles['paths'] = $filename_list;
		$attachedfiles['names'] = $mimefilename_list;
		$attachedfiles['mimes'] = $mimetype_list;
		if (is_array($attachedfiles) && count($attachedfiles) > 0) {
			$actioncomm->attachedfiles = $attachedfiles;
		}

		if (!empty($mimefilename_list) && is_array($mimefilename_list)) {
			$actioncomm->note_private = dol_concatdesc($actioncomm->note_private, "\n".$langs->transnoentities("AttachedFiles").': '.join(';', $mimefilename_list));
		}

		$actionid = $actioncomm->create($user);
		if ($actionid <= 0) {
			$error++;
			$this->error = $actioncomm->error;
			$this->errors = $actioncomm->errors;
		}

		// Commit or rollback
		if ($error) {
			$db->rollback();
			return -1 * $error;
		} else {
			$db->commit();
			return 1;
		}
	}


	public function createActionPaiementAbandono($user, $object,  $notrigger = 0)
	{
		global $conf, $langs, $db;
		$error = 0;

		$now = dol_now();

		// Clean parameters
		if (isset($this->fk_track_id)) {
			$this->fk_track_id = trim($this->fk_track_id);
		}

		if (isset($this->message)) {
			$this->message = trim($this->message);
		}

		$db->begin();
		include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($db);
		$actioncomm->type_code = 'AC_OTH_AUTO';
		$actioncomm->code = 'ABANDONED_PAIEMENT';
		if ($this->private) {
			$actioncomm->code = 'ABANDONED_PAIEMENT_PRIVATE';
		}
		$actioncomm->socid = '';
		$actioncomm->fk_project = $object->fk_projet;
		$actioncomm->label = "Pago cancelado " ;
		$actioncomm->note_private = 'Autor: ' . $user->firstname . ' ' . $user->lastname;
		$actioncomm->userassigned = array($user->id);
		$actioncomm->userownerid = $user->id;
		$actioncomm->datep = $now;
		$actioncomm->datef = $now;
		$actioncomm->percentage = -1; // percentage is not relevant for punctual events
		$actioncomm->elementtype = 'paiement';
		$actioncomm->fk_element = $object->id;

		$attachedfiles = array();
		$attachedfiles['paths'] = $filename_list;
		$attachedfiles['names'] = $mimefilename_list;
		$attachedfiles['mimes'] = $mimetype_list;
		if (is_array($attachedfiles) && count($attachedfiles) > 0) {
			$actioncomm->attachedfiles = $attachedfiles;
		}

		if (!empty($mimefilename_list) && is_array($mimefilename_list)) {
			$actioncomm->note_private = dol_concatdesc($actioncomm->note_private, "\n".$langs->transnoentities("AttachedFiles").': '.join(';', $mimefilename_list));
		}

		$actionid = $actioncomm->create($user);
		if ($actionid <= 0) {
			$error++;
			$this->error = $actioncomm->error;
			$this->errors = $actioncomm->errors;
		}

		// Commit or rollback
		if ($error) {
			$db->rollback();
			return -1 * $error;
		} else {
			$db->commit();
			return 1;
		}
	}

	public function createActionBillTimbre($user, $object,  $notrigger = 0)
	{
		global $conf, $langs, $db;
		$error = 0;
		$now = dol_now();

		// Clean parameters
		if (isset($this->fk_track_id)) {
			$this->fk_track_id = trim($this->fk_track_id);
		}

		if (isset($this->message)) {
			$this->message = trim($this->message);
		}

		$db->begin();
		include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($db);
		$actioncomm->type_code = 'AC_OTH_AUTO';
		$actioncomm->code = 'BILL_TIMBRADA';	
		if ($this->private) {
			$actioncomm->code = 'BILL_TIMBRADA_PRIVATE';
		}
		$actioncomm->socid = $object->fk_soc;
		$actioncomm->fk_project = $object->fk_projet;
		$actioncomm->label = "Timbre a la factura  " . $object->ref;
		$actioncomm->note_private = 'Autor: ' . $user->firstname . ' ' . $user->lastname . " Timbre a la factura  " . $object->ref;
		$actioncomm->userassigned = array($user->id);
		$actioncomm->userownerid = $user->id;
		$actioncomm->datep = $now;
		$actioncomm->datef = $now;
		$actioncomm->percentage = -1; // percentage is not relevant for punctual events
		$actioncomm->elementtype = 'invoice';
		$actioncomm->fk_element = $object->id;

		$attachedfiles = array();
		$attachedfiles['paths'] = $filename_list;
		$attachedfiles['names'] = $mimefilename_list;
		$attachedfiles['mimes'] = $mimetype_list;
		if (is_array($attachedfiles) && count($attachedfiles) > 0) {
			$actioncomm->attachedfiles = $attachedfiles;
		}

		if (!empty($mimefilename_list) && is_array($mimefilename_list)) {
			$actioncomm->note_private = dol_concatdesc($actioncomm->note_private, "\n".$langs->transnoentities("AttachedFiles").': '.join(';', $mimefilename_list));
		}

		$actionid = $actioncomm->create($user);
		if ($actionid <= 0) {
			$error++;
			$this->error = $actioncomm->error;
			$this->errors = $actioncomm->errors;
		}
		// Commit or rollback
		if ($error) {
			$db->rollback();
			return -1 * $error;
		} else {
			$db->commit();
			return 1;
		}
	}

	public function createActionBillCancel($user, $object,  $notrigger = 0)
	{
		global $conf, $langs, $db;
		$error = 0;
		$now = dol_now();

		// Clean parameters
		if (isset($this->fk_track_id)) {
			$this->fk_track_id = trim($this->fk_track_id);
		}

		if (isset($this->message)) {
			$this->message = trim($this->message);
		}

		$db->begin();
		include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($db);
		$actioncomm->type_code = 'AC_OTH_AUTO';
		$actioncomm->code = 'BILL_CANCEL';	
		if ($this->private) {
			$actioncomm->code = 'BILL_CANCEL_PRIVATE';
		}
		$actioncomm->socid = $object->fk_soc;
		$actioncomm->fk_project = $object->fk_projet;
		$actioncomm->label = "Factura  " . $object->ref . ' cancelada. ';
		$actioncomm->note_private = 'Autor: ' . $user->firstname . ' ' . $user->lastname . " Factura  " . $object->ref . ' cancelada. ';
		$actioncomm->userassigned = array($user->id);
		$actioncomm->userownerid = $user->id;
		$actioncomm->datep = $now;
		$actioncomm->datef = $now;
		$actioncomm->percentage = -1; // percentage is not relevant for punctual events
		$actioncomm->elementtype = 'invoice';
		$actioncomm->fk_element = $object->id;

		$attachedfiles = array();
		$attachedfiles['paths'] = $filename_list;
		$attachedfiles['names'] = $mimefilename_list;
		$attachedfiles['mimes'] = $mimetype_list;
		if (is_array($attachedfiles) && count($attachedfiles) > 0) {
			$actioncomm->attachedfiles = $attachedfiles;
		}

		if (!empty($mimefilename_list) && is_array($mimefilename_list)) {
			$actioncomm->note_private = dol_concatdesc($actioncomm->note_private, "\n".$langs->transnoentities("AttachedFiles").': '.join(';', $mimefilename_list));
		}

		$actionid = $actioncomm->create($user);
		if ($actionid <= 0) {
			$error++;
			$this->error = $actioncomm->error;
			$this->errors = $actioncomm->errors;
		}
		// Commit or rollback
		if ($error) {
			$db->rollback();
			return -1 * $error;
		} else {
			$db->commit();
			return 1;
		}
	}


	//CC
	/**
	 * Verifica si el pago existe dentro de diarios
	 */
	public function cancelarPoliza($id)
	{
		global $conf, $db;

		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping WHERE fk_doc = " . (int) $id . " AND doc_type = 'bank'";
		$cnclsql = $db->query($sql);
		if ($db->num_rows($cnclsql) > 0) {
			return 1;
		} else {
			return 0;
		}

	}

	// /**
	//  * Verifica que el checkbox este marcado, esto solo cuando se registra en contabilidad 
	//  */
	public function verifyCancelPay($id)
	{
		global $conf, $db;

		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "paiement WHERE rowid = " . (int) $id . " AND date_cancel  IS NOT NULL";
		$sqlres = $db->query($sql);

		if ($db->num_rows($sqlres) > 0) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Funcion para obtener la fecha de cancelación de págo
	 */
	public function getDatecancel($id)
	{
		global $conf, $db;

		$sql = "SELECT date_cancel FROM " . MAIN_DB_PREFIX . "paiement WHERE rowid = " . (int) $id;
		$sqlres = $db->query($sql);
		if ($db->num_rows($sqlres) > 0) {
			$result = $db->fetch_array($sqlres);
			return $result['date_cancel'];
		} else {
			return null;
		}
	}


	/**
	 * Si no existe una poliza de pago previamente creada entonces almacena la fecha de cancelación.
	 */

	public function dateCancel($date, $id)
	{
		global $conf, $db;

		$fecha = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
		//  $hora_actual = date('Y-m-d');
		$ssql = "UPDATE " . MAIN_DB_PREFIX . "paiement SET date_cancel = '" . $fecha . "' WHERE rowid = " . (int) $id;
		$resql = $db->query($ssql);
		if ($resql) {
			return 1;
		} else {
			return 0;
		}
	}


	/**
	 * Verifica si no existe una poliza de cancelación de pago  previamente creada para evitar duplicaciones.
	 */
	public function getCancelacionPay($id)
	{
		global $conf, $db;

		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping WHERE fk_doc = " . (int) $id . " AND code_journal = 'DC'";
		$cnclsql = $db->query($sql);
		if ($db->num_rows($cnclsql) > 0) {
			return 1;
		} else {
			return 0;
		}
	}


	/**
	 * Funcion  para crear las respectivas polizas en contabilidad
	 */
	public function createPolizaPago($object, $tabpay = array(), $tabbq = array(), $tabtp = array(), $tabtype = array(), $journal, $journal_label)
	{

		global $db, $conf, $user, $langs;

		$now = dol_now();

		$accountingaccountcustomer = new AccountingAccount($db);
		$accountingaccountcustomer->fetch(null, $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER, true);

		$accountingaccountsupplier = new AccountingAccount($db);
		$accountingaccountsupplier->fetch(null, $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER, true);

		$accountingaccountpayment = new AccountingAccount($db);
		$accountingaccountpayment->fetch(null, $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT, true);

		$accountingaccountsuspense = new AccountingAccount($db);
		$accountingaccountsuspense->fetch(null, $conf->global->ACCOUNTING_ACCOUNT_SUSPENSE, true);

		$error = 0;
		foreach ($tabpay as $key => $val) {		// $key is rowid into llx_bank
			$date = dol_print_date($db->jdate($val["date"]), 'day');

			$ref = getSourceDocRef($val, $tabtype[$key]);

			$errorforline = 0;

			$totalcredit = 0;
			$totaldebit = 0;

			$db->begin();

			// Introduce a protection. Total of tabtp must be total of tabbq
			//var_dump($tabpay);
			//var_dump($tabtp);
			//var_dump($tabbq);exit;

			// Bank

			if (!$errorforline && is_array($tabbq[$key])) {
				// Line into bank account
				foreach ($tabbq[$key] as $k => $mt) {
					if ($mt) {
						$accountingaccount = new AccountingAccount($db);
						$accountingaccount->fetch(null, $k, true);	// $k is accounting bank account. TODO We should use a cache here to avoid this fetch
						$account_label = $accountingaccount->label;

						$reflabel = '';

						if (!empty($val['lib'])) {
							$reflabel .= dol_string_nohtmltag($val['lib']) . " - ";
						}

						$reflabel .= $langs->trans("Bank") . ' ' . dol_string_nohtmltag($val['bank_account_ref']);

						if (!empty($val['soclib'])) {
							$reflabel .= " - " . dol_string_nohtmltag($val['soclib']);
						}

						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->doc_ref = $ref;
						$bookkeeping->doc_type = 'bank';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = $val["fk_bank"];

						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_compte = $account_label;

						$bookkeeping->label_operation = $reflabel;
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
						$bookkeeping->debit = ($mt >= 0 ? $mt : 0);
						$bookkeeping->credit = ($mt < 0 ? -$mt : 0);
						$bookkeeping->code_journal = $journal;
						$bookkeeping->journal_label = $langs->transnoentities($journal_label);
						$bookkeeping->fk_user_author = $user->id;
						$bookkeeping->date_creation = $now;

						// No subledger_account value for the bank line but add a specific label_operation
						$bookkeeping->subledger_account = '';
						$bookkeeping->label_operation = $reflabel;
						$bookkeeping->entity = $conf->entity;

						$totaldebit += $bookkeeping->debit;
						$totalcredit += $bookkeeping->credit;

						$result = $bookkeeping->create($user);
						if ($result < 0) {
							if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
								$error++;
								$errorforline++;
								setEventMessages('Transaction for (' . $bookkeeping->doc_type . ', ' . $bookkeeping->fk_doc . ', ' . $bookkeeping->fk_docdet . ') were already recorded', null, 'warnings');
							} else {
								$error++;
								$errorforline++;
								setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
							}
						}
					}
				}
			}

			// Third party
			if (!$errorforline) {
				if (is_array($tabtp[$key])) {
					// Line into thirdparty account
					foreach ($tabtp[$key] as $k => $mt) {
						if ($mt) {
							$lettering = false;

							$reflabel = '';
							if (!empty($val['lib'])) {
								$reflabel .= dol_string_nohtmltag($val['lib']) . ($val['soclib'] ? " - " : "");
							}
							if ($tabtype[$key] == 'banktransfert') {
								$reflabel .= dol_string_nohtmltag($langs->transnoentitiesnoconv('TransitionalAccount') . ' ' . $account_transfer);
							} else {
								$reflabel .= dol_string_nohtmltag($val['soclib']);
							}

							$bookkeeping = new BookKeeping($db);
							$bookkeeping->doc_date = $val["date"];
							$bookkeeping->doc_ref = $ref;
							$bookkeeping->doc_type = 'bank';
							$bookkeeping->fk_doc = $key;
							$bookkeeping->fk_docdet = $val["fk_bank"];

							$bookkeeping->label_operation = $reflabel;
							$bookkeeping->montant = $mt;
							$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
							$bookkeeping->debit = ($mt < 0 ? -$mt : 0);
							$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
							$bookkeeping->code_journal = $journal;
							$bookkeeping->journal_label = $langs->transnoentities($journal_label);
							$bookkeeping->fk_user_author = $user->id;
							$bookkeeping->date_creation = $now;

							if ($tabtype[$key] == 'payment') {	// If payment is payment of customer invoice, we get ref of invoice
								$lettering = true;
								$bookkeeping->subledger_account = $k; // For payment, the subledger account is stored as $key of $tabtp
								$bookkeeping->subledger_label = $tabcompany[$key]['name']; // $tabcompany is defined only if we are sure there is 1 thirdparty for the bank transaction
								$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
								$bookkeeping->label_compte = $accountingaccountcustomer->label;
							} elseif ($tabtype[$key] == 'payment_supplier') {	// If payment is payment of supplier invoice, we get ref of invoice
								$lettering = true;
								$bookkeeping->subledger_account = $k; // For payment, the subledger account is stored as $key of $tabtp
								$bookkeeping->subledger_label = $tabcompany[$key]['name']; // $tabcompany is defined only if we are sure there is 1 thirdparty for the bank transaction
								$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER;
								$bookkeeping->label_compte = $accountingaccountsupplier->label;
							} elseif ($tabtype[$key] == 'payment_expensereport') {
								$bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
								$bookkeeping->subledger_label = $tabuser[$key]['name'];
								$bookkeeping->numero_compte = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
								$bookkeeping->label_compte = $accountingaccountpayment->label;
							} elseif ($tabtype[$key] == 'payment_salary') {
								$bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
								$bookkeeping->subledger_label = $tabuser[$key]['name'];
								$bookkeeping->numero_compte = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
								$bookkeeping->label_compte = $accountingaccountpayment->label;
							} elseif (in_array($tabtype[$key], array('sc', 'payment_sc'))) {   // If payment is payment of social contribution
								$bookkeeping->subledger_account = '';
								$bookkeeping->subledger_label = '';
								$accountingaccount->fetch(null, $k, true);	// TODO Use a cache
								$bookkeeping->numero_compte = $k;
								$bookkeeping->label_compte = $accountingaccount->label;
							} elseif ($tabtype[$key] == 'payment_vat') {
								$bookkeeping->subledger_account = '';
								$bookkeeping->subledger_label = '';
								$accountingaccount->fetch(null, $k, true);		// TODO Use a cache
								$bookkeeping->numero_compte = $k;
								$bookkeeping->label_compte = $accountingaccount->label;
							} elseif ($tabtype[$key] == 'payment_donation') {
								$bookkeeping->subledger_account = '';
								$bookkeeping->subledger_label = '';
								$accountingaccount->fetch(null, $k, true);		// TODO Use a cache
								$bookkeeping->numero_compte = $k;
								$bookkeeping->label_compte = $accountingaccount->label;
							} elseif ($tabtype[$key] == 'member') {
								$bookkeeping->subledger_account = '';
								$bookkeeping->subledger_label = '';
								$accountingaccount->fetch(null, $k, true);		// TODO Use a cache
								$bookkeeping->numero_compte = $k;
								$bookkeeping->label_compte = $accountingaccount->label;
							} elseif ($tabtype[$key] == 'payment_loan') {
								$bookkeeping->subledger_account = '';
								$bookkeeping->subledger_label = '';
								$accountingaccount->fetch(null, $k, true);		// TODO Use a cache
								$bookkeeping->numero_compte = $k;
								$bookkeeping->label_compte = $accountingaccount->label;
							} elseif ($tabtype[$key] == 'payment_various') {
								$bookkeeping->subledger_account = $k;
								$bookkeeping->subledger_label = $tabcompany[$key]['name'];
								$accountingaccount->fetch(null, $tabpay[$key]["account_various"], true);	// TODO Use a cache
								$bookkeeping->numero_compte = $tabpay[$key]["account_various"];
								$bookkeeping->label_compte = $accountingaccount->label;
							} elseif ($tabtype[$key] == 'banktransfert') {
								$bookkeeping->subledger_account = '';
								$bookkeeping->subledger_label = '';
								$accountingaccount->fetch(null, $k, true);		// TODO Use a cache
								$bookkeeping->numero_compte = $k;
								$bookkeeping->label_compte = $accountingaccount->label;
							} else {
								if ($tabtype[$key] == 'unknown') {	// Unknown transaction, we will use a waiting account for thirdparty.
									// Temporary account
									$bookkeeping->subledger_account = '';
									$bookkeeping->subledger_label = '';
									$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_SUSPENSE;
									$bookkeeping->label_compte = $accountingaccountsuspense->label;
								}
							}
							$bookkeeping->label_operation = $reflabel;
							$bookkeeping->entity = $conf->entity;

							$totaldebit += $bookkeeping->debit;
							$totalcredit += $bookkeeping->credit;

							$result = $bookkeeping->create($user);
							if ($result < 0) {
								if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
									$error++;
									$errorforline++;
									setEventMessages('Transaction for (' . $bookkeeping->doc_type . ', ' . $bookkeeping->fk_doc . ', ' . $bookkeeping->fk_docdet . ') were already recorded', null, 'warnings');
								} else {
									$error++;
									$errorforline++;
									setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
								}
							} else {
								if ($lettering && getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && getDolGlobalInt('ACCOUNTING_ENABLE_AUTOLETTERING')) {
									require_once DOL_DOCUMENT_ROOT . '/accountancy/class/lettering.class.php';
									$lettering_static = new Lettering($db);
									$nb_lettering = $lettering_static->bookkeepingLetteringAll(array($bookkeeping->id));
								}
							}
						}
					}
				} else {	// If thirdparty unknown, output the waiting account
					foreach ($tabbq[$key] as $k => $mt) {
						if ($mt) {
							$reflabel = '';
							if (!empty($val['lib'])) {
								$reflabel .= dol_string_nohtmltag($val['lib']) . " - ";
							}
							$reflabel .= dol_string_nohtmltag('WaitingAccount');

							$bookkeeping = new BookKeeping($db);
							$bookkeeping->doc_date = $val["date"];
							$bookkeeping->doc_ref = $ref;
							$bookkeeping->doc_type = 'bank';
							$bookkeeping->fk_doc = $key;
							$bookkeeping->fk_docdet = $val["fk_bank"];
							$bookkeeping->montant = $mt;
							$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
							$bookkeeping->debit = ($mt < 0 ? -$mt : 0);
							$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
							$bookkeeping->code_journal = $journal;
							$bookkeeping->journal_label = $langs->transnoentities($journal_label);
							$bookkeeping->fk_user_author = $user->id;
							$bookkeeping->date_creation = $now;
							$bookkeeping->label_compte = '';
							$bookkeeping->label_operation = $reflabel;
							$bookkeeping->entity = $conf->entity;

							$totaldebit += $bookkeeping->debit;
							$totalcredit += $bookkeeping->credit;

							$result = $bookkeeping->create($user);

							if ($result < 0) {
								if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
									$error++;
									$errorforline++;
									setEventMessages('Transaction for (' . $bookkeeping->doc_type . ', ' . $bookkeeping->fk_doc . ', ' . $bookkeeping->fk_docdet . ') were already recorded', null, 'warnings');
								} else {
									$error++;
									$errorforline++;
									setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
								}
							}
						}
					}
				}
			}

			if (price2num($totaldebit, 'MT') != price2num($totalcredit, 'MT')) {
				$error++;
				$errorforline++;
				setEventMessages('Try to insert a non balanced transaction in book for ' . $ref . '. Canceled. Surely a bug.', null, 'errors');
			}

			if (!$errorforline) {
				$db->commit();
			} else {
				//print 'KO for line '.$key.' '.$error.'<br>';
				$db->rollback();

				$MAXNBERRORS = 5;
				if ($error >= $MAXNBERRORS) {
					setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped") . ' (>' . $MAXNBERRORS . ')', null, 'errors');
					break; // Break in the foreach
				}
			}
		}

		if (empty($error) && count($tabpay) > 0) {
			setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
		} elseif (count($tabpay) == $error) {
			setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
		} else {
			setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
		}

		// $action = '';

		// // Must reload data, so we make a redirect
		// if (count($tabpay) != $error) {
		// 	$param = 'id_journal='.$id_journal;
		// 	$param .= '&date_startday='.$date_startday;
		// 	$param .= '&date_startmonth='.$date_startmonth;
		// 	$param .= '&date_startyear='.$date_startyear;
		// 	$param .= '&date_endday='.$date_endday;
		// 	$param .= '&date_endmonth='.$date_endmonth;
		// 	$param .= '&date_endyear='.$date_endyear;
		// 	$param .= '&in_bookkeeping='.$in_bookkeeping;
		// 	header("Location: ".$_SERVER['PHP_SELF'].($param ? '?'.$param : ''));
		// 	exit;

		$action = '';
		header("Location: " . $_SERVER['PHP_SELF']);
		// exit;
	}


	/**
	 * Funcion para crear la poliza de cancelación de pago
	 */
	public function transformPolizaPay($id, $date)
	{
		global $conf, $db;
		$error = 0;

		$db->begin();

		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			$fecha = $date;
		} else {
			$fecha = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
		}


		//	SELECT piece_num   FROM llx_accounting_bookkeeping where  fk_doc = 13  AND  doc_type = 'bank'; //generar la ref 
		// $refsql = "SELECT a.ref, e.piece_num 
		//     from llx_facture a 
		//     inner join llx_paiement_facture b on a.rowid = b.fk_facture 
		//     inner join llx_paiement  c ON b.fk_paiement = c.rowid
		//     inner join llx_bank_url d on c.rowid = d.url_id and d.type = 'payment'
		//     inner join llx_accounting_bookkeeping e ON e.fk_doc = d.fk_bank and doc_type = 'bank'
		//     where c.rowid = " . $id . " GROUP BY a.ref, e.piece_num ";

		$refsql = " SELECT piece_num, SUBSTRING_INDEX(doc_ref, 'Factura ', -1) AS ref_factura
					FROM llx_accounting_bookkeeping
					WHERE fk_doc = " . $id . " AND doc_type = 'bank'  GROUP BY  piece_num, ref_factura ";

		$reffsql = $db->query($refsql);

		$num = $reffsql->num_rows;
		$i = 0;

		if ($reffsql) {

			// while ($ref_fac = $db->fetch_object($reffsql)) {
			while ($i < $num) {
				$ref_fac = $db->fetch_object($reffsql);
				// Select data from the original entries based on doc_ref
				$sql = ' SELECT doc_date, doc_type, doc_ref, fk_doc, fk_docdet, thirdparty_code, subledger_account, subledger_label,';
				$sql .= ' numero_compte, label_compte, label_operation, debit, credit, montant, sens, fk_user_author,';
				$sql .= ' import_key, code_journal, journal_label';
				$sql .= ' FROM ' . MAIN_DB_PREFIX . 'accounting_bookkeeping WHERE doc_ref LIKE "%' . $ref_fac->ref_factura . '%"';
				$sql .= ' and piece_num = ' . $ref_fac->piece_num;

				$resql = $db->query($sql);


				$num2 = $resql->num_rows;
				$r = 0;

				if ($resql) {
					// Obtener el último piece_num
					$sqlMaxPieceNum = 'SELECT MAX(piece_num) AS max_piece_num FROM ' . MAIN_DB_PREFIX . 'accounting_bookkeeping';
					$resMaxPieceNum = $db->query($sqlMaxPieceNum);
					$maxPieceNum = $db->fetch_object($resMaxPieceNum)->max_piece_num;
					$newPieceNum = $maxPieceNum + 1;
					while ($r < $num2) {
						$objres = $db->fetch_object($resql);
						// list($dia, $mes, $anio) = explode('/', $date);
						// $newDate = $anio . '-' . $mes . '-' . $dia;

						$sqlcrt = 'INSERT INTO ' . MAIN_DB_PREFIX . 'accounting_bookkeeping (doc_date, doc_type, doc_ref, fk_doc, fk_docdet,';
						$sqlcrt .= ' thirdparty_code, subledger_account, subledger_label, numero_compte, label_compte,';
						$sqlcrt .= ' label_operation, debit, credit, montant, sens, fk_user_author, import_key, code_journal,';
						$sqlcrt .= ' journal_label, piece_num) VALUES (';
						$sqlcrt .= "'" . $db->escape($fecha) . "', ";
						$sqlcrt .= "'" . $db->escape($objres->doc_type) . "', ";
						$sqlcrt .= "'" . $db->escape($objres->doc_ref . '-C') . "', ";
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

						$resinsert = $db->query($sqlcrt);

						if (!$resinsert) {
							$error = 1;
						}
						$r++;

					}
					$sqls = " select fk_facture  FROM  llx_paiement_facture
                        WHERE fk_paiement = " . $id;
					$resqls = $db->query($sqls);
					$facture = $db->fetch_object($resqls)->fk_facture;

					$sqll = "UPDATE llx_facture SET paye = 0, fk_statut = 1
                WHERE rowid = " . $facture;
					$resqll = $db->query($sqll);

					$ssql = "UPDATE llx_paiement_facture SET fk_facture = null  WHERE fk_paiement = " . $id;
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
			$i++;
		}

	}

	

}

?>