<?php

namespace App\Models;

use App\Models\ApiModel;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

class Setting extends ApiModel
{
    protected $table = 'setting';
    public $timestamps = true;

    // Allow all fields to be filled.
    protected $guarded = [];

    protected $primaryKey = 'name';
    public $incrementing = false;

    protected $rules = [
        'name' => 'required|string',
        'value' => 'required|string|nullable'
    ];

    public $appends = [
        'type',
        'description',
        'is_credential',
        'options'
    ];

    public static $cache = [];

    /*
     * Each setting must be described in the table below.
     *
     * The definitions are:
     * description: single line detail on what the setting is
     * type: the value type (bool,string,json,email,date,datetime,integer,float)
     * is_credential (optional) - set true if the setting is a credential and should not be included in a redact database dump
     * options: array of possible options format is [ 'option', 'description' ]
     */


    const DESCRIPTIONS = [
        'AccountCreationEmail' => [
            'description' => 'Alert email address when accounts register',
            'type' => 'email',
        ],

        'AdminEmail' => [
            'description' => 'Ranger Tech Team Email Address',
            'type' => 'email',
        ],

        'AllowSignupsWithoutPhoto' => [
            'description' => 'Allow shift signups without requiring an approved photo',
            'type' => 'bool',
        ],

        'BmidTestToken' => [
            'description' => 'BMID test token to trigger Lambase bug',
            'type' => 'string',
        ],

        'BroadcastClubhouseNotify' => [
            'description' => 'Enable RBS notification of new Clubhouse messages',
            'type' => 'bool',
        ],

        'BroadcastClubhouseSandbox' => [
            'description' => 'Enable RBS Clubhouse Message sandbox mode (Clubhouse messages not created)',
            'type' => 'bool',
        ],

        'BroadcastMailSandbox' => [
            'description' => 'Enable RBS sandbox email mode',
            'type' => 'bool',
        ],

        'BroadcastSMSService' => [
            'description' => 'Ranger Broadcast SMS Service',
            'type' => 'string',
            'options' => [
                ['twilio', 'deliver SMS messages via Twilio'],
                ['sandbox', 'No SMS sent - developer mode'],
            ]
        ],

        'BroadcastSMSSandbox' => [
            'description' => 'Sandbox SMS messages',
            'type' => 'bool',
        ],

        'DailyReportEmail' => [
            'description' => 'Email address to send the Clubhouse Daily Report',
            'type' => 'email',
        ],

        'EditorUrl' => [
            'description' => 'The script URL of the WYSIWYG editor (currently TinyMCE)',
            'type' => 'url'
        ],

        'GeneralSupportEmail' => [
            'description' => 'General Ranger Email Address',
            'type' => 'email',
        ],

        'JoiningRangerSpecialTeamsUrl' => [
            'description' => 'How To Join Ranger Special Teams Document URL',
            'type' => 'string',
        ],

        'MealDates' => [
            'description' => 'Commissary dates and hours',
            'type' => 'string',
        ],

        'MealInfoAvailable' => [
            'description' => 'True if meal information is available.',
            'type' => 'bool',
        ],

        'MentorEmail' => [
            'description' => 'Mentor Cadre email. Shown to Alphas when suggesting contacting the cadre.',
            'type' => 'email'
        ],

        'MotorpoolPolicyEnable' => [
            'description' => 'Enable Motorpool Policy Page',
            'type' => 'bool',
        ],

         'OnboardAlphaShiftPrepLink' => [
            'description' => 'Used by the Onboarding Checklist for PNVs. Link to how to prep for your Alpha Shift in the Ranger Manual',
            'type' => 'string'
        ],

        'OnlineTrainingEnabled' => [
            'description' => 'Enable online training link',
            'type' => 'bool'
        ],

        'OnlineTrainingUrl' => [
            'description' => 'Online Training Url',
            'type' => 'string'
        ],

        'OnlineTrainingDisabledAllowSignups' => [
            'description' => 'Enable shift signups even if Online Training is disabled',
            'type' => 'bool',
        ],

        'DoceboDomain' => [
            'description' => 'Docebo learning domain to use',
            'type' => 'string'
        ],

        'DoceboClientId' => [
            'description' => 'Docebo Client ID - used to manage users and query course completion',
            'type' => 'string',
            'is_credential' => true,
        ],

        'DoceboClientSecret' => [
            'description' => 'Docebo Client Seret - used to manage users and query course completion',
            'type' => 'string',
            'is_credential' => true,
        ],

        'DoceboUsername' => [
            'description' => 'Docebo username - used to manage users and query course compl etion',
            'type' => 'string',
            'is_credential' => true,
        ],

        'DoceboPassword' => [
            'description' => 'Docebo password - used to manage users and query course completion',
            'type' => 'string',
            'is_credential' => true,
        ],

        'DoceboHalfCourseId' => [
            'description' => 'Docebo Half Ranger course id (record id, not course code) for active Rangers (2+ years)',
            'type' => 'integer',
        ],

        'DoceboFullCourseId' => [
            'description' => 'Docebo full Ranger course id (record id, not course code) for PNVs, Auditors, Binaries, and Inactive Rangers',
            'type' => 'integer',
        ],

        'PersonnelEmail' => [
            'description' => 'Ranger Personnel Email Address',
            'type' => 'email',
        ],

        'PhotoAnalysisEnabled' => [
            'description' => 'Run all uploaded photos through AWS Rekognition face detection',
            'type' => 'bool',
        ],

        'PhotoRekognitionAccessKey' => [
            'description' => 'AWS Rekognition Access Key used for BMID photo analysis',
            'type' => 'string',
            'is_credential' => true
        ],

        'PhotoRekognitionAccessSecret' => [
            'description' => 'AWS Rekognition Secret Key used for BMID photo analysis',
            'type' => 'string',
            'is_credential' => true
        ],

        'PhotoPendingNotifyEmail' => [
            'description' => 'Email(s) to notify when photos are queued up for review. (nightly mail)',
            'type' => 'email'
        ],

        'PhotoUploadEnable' => [
            'description' => 'Enable Photo Uploading',
            'type' => 'bool',
        ],

        'RadioCheckoutAgreementEnabled' => [
            'description' => 'Allows the Radio Checkout Agreement to be signed',
            'type' => 'bool',
        ],

        'RadioInfoAvailable' => [
            'description' => 'True if radio information has been uploaded.',
            'type' => 'bool',
        ],

        'RangerFeedbackFormUrl' => [
            'description' => 'Ranger Feedback Form URL',
            'type' => 'string',
        ],

        'RangerManualUrl' => [
            'description' => 'The current Ranger Manual document',
            'type' => 'string'
        ],

        'RangerPoliciesUrl' => [
            'description' => 'Ranger Policy Document URL',
            'type' => 'string',
        ],

        'RpTicketThreshold' => [
            'description' => 'Credit threshold for a reduced price ticket. Shown on the Schedule and Ticket announce pages',
            'type' => 'float',
        ],

        'SFEnableWritebacks' => [
            'description' => 'Enable Salesforce Object Update',
            'type' => 'bool',
        ],

        'SFprdAuthUrl' => [
            'description' => 'Salesforce Production Authentication URL',
            'type' => 'string',
            'is_credential' => true,
        ],

        'SFprdClientId' => [
            'description' => 'Salesforce Production Client ID',
            'type' => 'string',
            'is_credential' => true,
        ],

        'SFprdClientSecret' => [
            'description' => 'Salesforce Production Client Secret',
            'type' => 'string',
            'is_credential' => true,
        ],

        'SFprdPassword' => [
            'description' => 'Salesforce Production Password (login password + security token)',
            'type' => 'string',
            'is_credential' => true,
        ],

        'SFprdUsername' => [
            'description' => 'Salesforce Production Username',
            'type' => 'string',
            'is_credential' => true,
        ],

        'ScTicketThreshold' => [
            'description' => 'Credit threshold for staff credential. Shown on the Schedule and Ticket announce pages',
            'type' => 'float',
        ],

        'SendWelcomeEmail' => [
            'description' => 'Enable Welcome email when an account is created',
            'type' => 'bool',
        ],

        'ShiftSignupFromEmail' => [
            'description' => 'From email  address for shift sign up messages',
            'type' => 'email',
        ],

        'ShirtLongSleeveHoursThreshold' => [
            'description' => 'Hour threshold to earn a long sleeve shirt',
            'type' => 'integer',
        ],

        'ShirtShortSleeveHoursThreshold' => [
            'description' => 'Hour threshold to earn a short sleeve shirt/t-shirt',
            'type' => 'integer',
        ],

        'TAS_Alpha_FAQ' => [
            'description' => 'Alpha WAP FAQ Link',
            'type' => 'string',
        ],

        'TAS_BoxOfficeOpenDate' => [
            'description' => 'Playa Box Office Opening date and time',
            'type' => 'datetime',
        ],

        'TAS_DefaultAlphaWAPDate' => [
            'description' => 'Default Alpha WAP Access Date',
            'type' => 'date',
        ],

        'TAS_DefaultSOWAPDate' => [
            'description' => 'Default WAP SO Access Date',
            'type' => 'date',
        ],

        'TAS_DefaultWAPDate' => [
            'description' => 'Default WAP Access Date',
            'type' => 'date',
        ],

        'TAS_Delivery' => [
            'description' => 'Ticket Delivery View',
            'type' => 'string',
            'options' => [
                ['none', 'not available yet'],
                ['view', 'ticket announcement'],
                ['accept', 'allow ticket submissions'],
                ['frozen', 'ticket window is closed'],
            ]
        ],

        'TAS_Email' => [
            'description' => 'Ranger Ticketing Support Email',
            'type' => 'email',
        ],

        'TAS_Pickup_Locations' => [
            'description' => 'Locations w/hours to pickup staff credentials and will-call items. Shown on the ticketing page',
            'type' => 'string',
        ],

        'TAS_SubmitDate' => [
            'description' => 'Ticketing Submission Deadline',
            'type' => 'string',
        ],

        'TAS_Ticket_FAQ' => [
            'description' => 'Ticketing FAQ Link',
            'type' => 'string',
        ],

        'TAS_Tickets' => [
            'description' => 'Event Ticket Mode',
            'type' => 'string',
            'options' => [
                ['none', 'not available yet'],
                ['view', 'ticket announcement'],
                ['accept', 'allow ticket submissions'],
                ['frozen', 'ticket window is closed'],
            ]
        ],

        'TAS_VP' => [
            'description' => 'Vehicle Pass Mode',
            'type' => 'string',
            'options' => [
                ['none', 'not available yet'],
                ['view', 'ticket announcement'],
                ['accept', 'allow ticket submissions'],
                ['frozen', 'ticket window is closed'],
            ]
        ],

        'TAS_VP_FAQ' => [
            'description' => 'Vehicle Pass FAQ Link',
            'type' => 'string',
        ],

        'TAS_WAP' => [
            'description' => 'Work Access Pass Mode',
            'type' => 'string',
            'options' => [
                ['none', 'not available yet'],
                ['view', 'ticket announcement'],
                ['accept', 'allow ticket submissions'],
                ['frozen', 'ticket window is closed'],
            ]
        ],

        'TAS_WAPDateRange' => [
            'description' => 'WAP allowable date range. Format: MM/DD-MM/DD',
            'type' => 'string',
        ],

        'TAS_WAPSO' => [
            'description' => 'WAP SO Mode',
            'type' => 'string',
            'options' => [
                ['none', 'not available yet'],
                ['view', 'ticket announcement'],
                ['accept', 'allow ticket submissions'],
                ['frozen', 'ticket window is closed'],
            ]
        ],

        'TAS_WAPSOMax' => [
            'description' => 'Max. WAP SO Count',
            'type' => 'integer',
        ],

        'TAS_WAP_FAQ' => [
            'description' => 'WAP FAQ Link',
            'type' => 'string',
        ],

        'ThankYouCardsHash' => [
            'description' => 'Thank You card page password. SHA-256 encoded.',
            'type' => 'string',
            'is_credential' => true,
        ],

        'TicketVendorEmail' => [
            'description' => 'Ticketing Vendor Support Email',
            'type' => 'email',
        ],

        'TicketVendorName' => [
            'description' => 'Ticketing Vendor Name',
            'type' => 'string',
        ],

        'TicketingPeriod' => [
            'description' => 'Ticketing Period / Season',
            'type' => 'string',
            'options' => [
                ['offseason', 'off season - show banked tickets'],
                ['announce', 'announce - tickets have been awarded but ticketing window is not open'],
                ['open', 'open - tickets can be claimed and TAS_Tickets, TAS_VP, TAS_WAP, TAS_WAPSO, TAS_Delivery come into play'],
                ['closed', 'closed - show claims and banks. Changes not directly allowed'],
            ]
        ],

        'TicketsAndStuffEnablePNV' => [
            'description' => 'Enable Ticketing Page for PNVs',
            'type' => 'bool',
        ],

        'TimesheetCorrectionEnable' => [
            'description' => 'Allow users to submit Timesheet Corrections',
            'type' => 'bool',
        ],

        'TrainingAcademyEmail' => [
            'description' => 'Training Academy Email',
            'type' => 'email',
        ],

        'TrainingSignupFromEmail' => [
            'description' => 'From email address for training sign up messages',
            'type' => 'email',
        ],

        'TwilioAccountSID' => [
            'description' => 'Twilio Account SID',
            'type' => 'string',
            'is_credential' => true,
        ],

        'TwilioAuthToken' => [
            'description' => 'Twilio Authentication Token',
            'type' => 'string',
            'is_credential' => true,
        ],

        'TwilioServiceId' => [
            'description' => 'Twilio Service ID of SMS Channel',
            'type' => 'string',
            'is_credential' => true,
        ],

        'TwilioStatusCallbackUrl' => [
            'description' => 'Twilio Status Callback URL (not implemented currently)',
            'type' => 'string',
        ],

        'VCEmail' => [
            'description' => 'Ranger Volunteer Coordinator Address',
            'type' => 'email',
        ],

        'VehiclePendingEmail' => [
            'description' => 'Email(s) to notify when vehicle requests are queued up for review. (nightly mail)',
            'type' => 'email'
        ],
    ];

    /*
     * Find a setting. Must be defined in the DESCRIPTIONS table
     */

    public static function find($name)
    {
        $desc = self::DESCRIPTIONS[$name] ?? null;

        if (!$desc) {
            // Setting is not defined.
            return null;
        }

        // Lookup the value
        return Setting::where('name', $name)->firstOrNew(['name' => $name]);
    }

    public static function findOrFail($name)
    {
        $row = self::find($name);

        if ($row) {
            return $row;
        }

        throw (new ModelNotFoundException)->setModel(Setting::class, $name);
    }

    public static function findAll()
    {
        $rows = Setting::all()->keyBy('name');

        $settings = collect([]);
        foreach (self::DESCRIPTIONS as $name => $desc) {
            $settings[] = $rows[$name] ?? new Setting(['name' => $name]);
        }

        return $settings->sortBy('name')->values();
    }

    public static function getValue($name, $throwOnEmpty = false)
    {
        if (is_array($name)) {
            $rows = self::select('name', 'value')->whereIn('name', $name)->get()->keyBy('name');
            $settings = [];
            foreach ($name as $setting) {
                $row = $rows[$setting] ?? null;
                $desc = self::DESCRIPTIONS[$setting] ?? null;
                if (!$desc) {
                    throw new \InvalidArgumentException("'$setting' is an unknown setting.");
                }


                $settings[$setting] = $row ? self::castValue($desc['type'], $row->value) : null;
                if ($throwOnEmpty && self::notEmpty($settings[$setting])) {
                    throw new \RuntimeException("Setting '$setting' is empty.");
                }
            }

            return $settings;
        } else {
            $desc = self::DESCRIPTIONS[$name] ?? null;
            if (!$desc) {
                throw new \InvalidArgumentException("'$name' is an unknown setting.");
            }

            if (isset(self::$cache[$name])) {
                return self::$cache[$name];
            }

            $row = self::select('value')->where('name', $name)->first();

            $value = $row ? self::castValue($desc['type'], $row->value) : null;
            if ($throwOnEmpty && self::notEmpty($value)) {
                throw new \RuntimeException("Setting '$name' is empty.");
            }
            self::$cache[$name] = $value;
            return $value;
        }
    }

    public static function castValue($type, $value)
    {
        // Convert the values
        switch ($type) {
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int)$value;
            default:
                return $value;
        }
    }

    public static function notEmpty($value)
    {
        return !is_bool($value) && empty($value);
    }

    public function getTypeAttribute()
    {
        $desc = self::DESCRIPTIONS[$this->name] ?? null;
        return $desc ? $desc['type'] : null;
    }

    public function getDescriptionAttribute()
    {
        $desc = self::DESCRIPTIONS[$this->name] ?? null;
        return $desc ? $desc['description'] : null;
    }

    public function getOptionsAttribute()
    {
        $desc = self::DESCRIPTIONS[$this->name] ?? null;
        return $desc ? ($desc['options'] ?? null) : null;
    }

    public function getIsCredentialAttribute()
    {
        $desc = self::DESCRIPTIONS[$this->name] ?? null;
        return $desc ? ($desc['is_credential'] ?? false) : null;
    }
}
