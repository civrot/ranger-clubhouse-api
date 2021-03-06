<?php

namespace App\Models;

use App\Models\ApiModel;

use App\Models\Person;

class PersonLanguage extends ApiModel
{
    const OFF_DUTY = 1;
    const ON_DUTY = 2;
    const HAS_RADIO = 3;

    const LANGUAGE_NAME_LENGTH = 32;

    protected $table = 'person_language';

    /*
     * All fields are mass-assignable
      * @var string
      */
    protected $guarded = [];

    /**
     * Don't use created_at/updated_at.
     * @var bool
     */
    public $timestamps = false;

    public function person() {
        return $this->belongsTo(Person::class);
    }

    /*
     * Retrieve a comma-separated list of language spoken by a person
     * @var integer $person_id Person to lookup
     * @return string a command list of languages
     */

    public static function retrieveForPerson($person_id): string {
        $languages = self::where('person_id', $person_id)->pluck('language_name')->toArray();

        return join(', ', $languages);
    }

    /*
     * Update the languages spoken by a person
     * @var integer $person_id Person to update
     * @var string $spoken a comman separated language list
     */

    public static function updateForPerson($person_id, $language) {
        self::where('person_id', $person_id)->delete();

        $languages = preg_split('/([,\.;&]|\band\b)/', $language);

        foreach ($languages as $name) {
            $tongue = trim($name);

            if (empty($name)) {
                continue;
            }

            $name = substr($name, 0, self::LANGUAGE_NAME_LENGTH);
            self::create([ 'person_id' => $person_id, 'language_name' => $name]);
        }
    }

    public static function findSpeakers($language, $includeOffSite, $status)
    {
        $sql = self::select('language_name', 'person.id as person_id', 'callsign')
                ->where('language_name', 'like', '%'.$language.'%')
                ->join('person', 'person.id', '=', 'person_language.person_id')
                ->whereNotIn('person.status', [ 'prospective', 'past prospective', 'auditor'])
                ->orderBy('callsign');

        if (!$includeOffSite) {
            $sql = $sql->where('person.on_site', 1);
        }

        switch ($status) {
        case PersonLanguage::OFF_DUTY:
                // Nothing needs to be done..
                break;

        case PersonLanguage::ON_DUTY:
            $sql = $sql->join('timesheet', 'person.id', '=', 'timesheet.person_id')
                    ->whereNotNull('timesheet.on_duty')
                    ->whereNull('timesheet.off_duty');
            break;

        case PersonLanguage::HAS_RADIO:
            $sql = $sql->join('asset_person', 'asset_person.id', '=', 'person_language.person_id')
                    ->whereNotNull('asset_person.checked_out')
                    ->whereNull('asset_person.checked_in');
            break;

        default:
            throw new \InvalidArgumentException("Unknown status [$status]");
        }

        return $sql->get();
    }

    public static function retrieveAllOnSiteSpeakers()
    {
        $personId = Person::where('on_site', true)
                    ->whereIn('status', Person::LIVE_STATUSES)
                    ->pluck('id');

        $languages = self::whereIn('person_id', $personId)
                    ->with([ 'person' => function ($q) {
                        $q->select('id', 'callsign');
                        $q->orderBy('callsign');
                    } ])
                    ->orderBy('language_name')
                    ->get()
                    ->filter(function ($row) { return $row->person != null;} )
                    ->groupBy(function ($r) {
                        return strtolower(trim($r->language_name));
                    });

        $rows = $languages->map(function ($people, $name) {
            return [
                'language'  => $people[0]->language_name,
                'people'    => $people->map(function ($row) {
                    return [
                        'id'       => $row->person_id,
                        'callsign' => $row->person->callsign
                    ];
                })->values()
            ];
        })->sortBy('language', SORT_NATURAL|SORT_FLAG_CASE)->values();

        return $rows;
    }
}
