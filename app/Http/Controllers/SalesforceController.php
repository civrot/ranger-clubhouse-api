<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

use App\Lib\PotentialClubhouseAccountFromSalesforce;
use App\Lib\SalesforceClubhouseInterface;

use App\Models\ErrorLog;
use App\Models\Person;
use App\Models\PersonIntakeNote;
use App\Models\PersonPosition;
use App\Models\PersonRole;
use App\Models\PersonStatus;

use App\Mail\WelcomeMail;

class SalesforceController extends ApiController
{
    public function config() {
        return response()->json([
            'config' => [
                'SFEnableWritebacks' => setting('SFEnableWritebacks'),
                'SendWelcomeEmail' => setting('SendWelcomeEmail'),
            ]
        ]);
    }

    public function import() {
        $params = request()->validate([
            'create_accounts'     => 'sometimes|boolean|required',
            'showall'             => 'sometimes|boolean',
            'update_sf'           => 'sometimes|boolean',
            'non_test_accounts'   => 'sometimes|boolean',
            'reset_test_accounts' => 'sometimes|boolean',
        ]);

        $createAccounts = $params['create_accounts'] ?? false;
        $resetTestAccounts = $params['reset_test_accounts'] ?? false;
        $updateSf = $params['update_sf'] ?? false;
        $nonTestAccounts = $params['non_test_accounts'] ?? false;

        $showall = $params['showall'] ?? false;

        $queryOptions = "testing";

        if ($resetTestAccounts) {
            $createAccounts = false;
            $updateSf = false;
            $nonTestAccounts = false;
            $showall = false;
        }

        if ($showall) {
            $createAccounts = false;
            $resetTestAccounts = false;
            $updateSf = false;
            $nonTestAccounts = false;
            $queryOptions = 'showall';
        } else if ($createAccounts || $nonTestAccounts) {
            $queryOptions = '';
        }

        $sfch = new SalesforceClubhouseInterface();
        if (!$sfch->auth('production')) {
            return response()->json([
                'status'    => 'error',
                'message'   => "Authentication error: {$sfch->sf->errorMessage}"
            ]);
        }

        $r = $sfch->queryAccountsReadyForImport($queryOptions);
        if ($r == false) {
            return response()->json([
                'status' => 'error',
                'message' => 'Query accounts failed '.  $sfch->sf->errorMessage
            ]);
        }

        $accounts = [ ];
        $errors = [];

        foreach ($r->records as $id => $obj) {
            $pca = new PotentialClubhouseAccountFromSalesforce;
            $pca->convertFromSalesforceObject($obj);
            if ($pca->status == "null") {
                continue;
            }

            if ($pca->status == "ready") {
                $pca->checkIfAlreadyExists();
            }

            // Only reset accounts if we're not doing anything else.
            // Some of these checks are redundant w/ the above but we're
            // being extra careful here 'cause the gun is loaded
           if ($resetTestAccounts) {
               $pca->status = "reset";
               $sfch->updateSalesforceVCStatus($pca);
           }

            if (($pca->status == "ready" || $pca->status == 'existing') && $createAccounts) {
                if (!$this->importPerson($sfch, $pca, $updateSf)) {
                    $account['message'] = $pca->message;
                }
            }

            $account = [
                'status'                        => $pca->status,
                'message'                       => $pca->message,
                'applicant_type'                => $pca->applicant_type,
                'salesforce_ranger_object_id'   => $pca->salesforce_ranger_object_id,
                'salesforce_ranger_object_name' => $pca->salesforce_ranger_object_name,
                'first_name'                    => $pca->firstname,
                'last_name'                     => $pca->lastname,
                'street1'                       => $pca->street1,
                'city'                          => $pca->city,
                'state'                         => $pca->state,
                'zip'                           => $pca->zip,
                'country'                       => $pca->country,
                'phone'                         => $pca->phone,
                'email'                         => $pca->email,
                'emergency_contact'             => $pca->emergency_contact,
                'bpguid'                        => $pca->bpguid,
                'sfuid'                         => $pca->sfuid,
                'chuid'                         => $pca->chuid,
                'longsleeveshirt_size_style'    => $pca->longsleeveshirt_size_style,
                'teeshirt_size_style'           => $pca->teeshirt_size_style,
                'known_pnv_names'               => $pca->known_pnv_names,
                'known_ranger_names'            => $pca->known_ranger_names,
                'callsign'                      => $pca->callsign,
                'vc_status'                     => $pca->vc_status,
                'why_ranger_comments'           => $pca->why_ranger_comments,
            ];

            if ($pca->existingPerson) {
                $account['existing_person'] = $pca->existingPerson;
            }


            $accounts[] = $account;
        }

        return response()->json([
            'status'    => 'success',
            'accounts'  => $accounts,
        ]);
    }

    private function importPerson($sfch, $pca, $updateSf) {
        if ($pca->status == 'existing') {
            $person = $pca->existingPerson;
            $isNew = false;
        } else {
            $person = new Person;
            $isNew = true;
        }

        $person->callsign          = $pca->callsign;
        $person->callsign_approved = 1;
        $person->first_name        = $pca->firstname;
        $person->last_name         = $pca->lastname;
        $person->street1           = $pca->street1;
        $person->city              = $pca->city;
        $person->state             = $pca->state;
        $person->zip               = $pca->zip;
        $person->country           = $pca->country;
        $person->home_phone        = $pca->phone;
        $person->email             = $pca->email;
        $person->bpguid            = $pca->bpguid;
        $person->sfuid             = $pca->sfuid;
        $person->emergency_contact = $pca->emergency_contact;

        $person->known_rangers = $pca->known_ranger_names;
        $person->known_pnvs = $pca->known_pnv_names;

        $person->longsleeveshirt_size_style = empty($pca->longsleeveshirt_size_style) ? 'Unknown' : $pca->longsleeveshirt_size_style;
        $person->teeshirt_size_style        = empty($pca->teeshirt_size_style) ? 'Unknown' : $pca->teeshirt_size_style;

        if ($isNew) {
            $person->password = 'abcdef';
        } else {
            $oldStatus = $person->status;
        }

        $person->status = Person::PROSPECTIVE;
        $person->auditReason = 'salesforce import';

        try {
            if (!$person->save()) {
                $message = [];
                foreach ($person->getErrors() as $column => $errors) {
                    $message[] = "$column: ".implode(' & ', $errors);
                }
                $pca->message = ($isNew ? 'Creation' : 'Update').' error: '.implode(', ', $messages);
                $pca->status = "failed";
                ErrorLog::record('salesforce-import-fail', [
                    'person' => $person,
                    'errors' => $person->getErrors()
                ]);
                return false;
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $pca->message = "SQL Error: ".$e->getMessage();
            $pca->status = "failed";
            ErrorLog::recordException($e, 'salesforce-import-fail', [ 'person' => $person ]);
            return false;
        }

        if ($isNew) {
            // Record the initial status for tracking through the Unified Flagging View
            PersonStatus::record($person->id, '', Person::PROSPECTIVE, 'salesforce import', Auth::id());
            // Setup the default roles & positions
            PersonRole::resetRoles($person->id, 'salesforce import', Person::ADD_NEW_USER);
            PersonPosition::resetPositions($person->id, 'salesforce import', Person::ADD_NEW_USER);
        } else {
            $person->changeStatus(Person::PROSPECTIVE, $oldStatus, 'salesforce import');
        }

        // Send a welcome email to the person if not an auditor
        if (setting('SendWelcomeEmail')) {
            mail_to($person->email, new WelcomeMail($person), true);
        }

        $pca->chuid = $person->id;
        $pca->status = 'succeeded';

        if (!empty($pca->why_ranger_comments)) {
            PersonIntakeNote::record($person->id, current_year(), 'vc', $pca->why_ranger_comments);
        }

        if ($updateSf) {
            $sfch->updateSalesforceVCStatus($pca);
            $sfch->updateSalesforceClubhouseImportStatusMessage($pca);
            $sfch->updateSalesforceClubhouseUserID($pca);

            if ($pca->status != 'succeeded') {
                return false;
            }
        }

        return true;
    }
}
