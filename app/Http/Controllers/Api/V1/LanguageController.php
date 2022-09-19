<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Model\BusinessSetting;

class LanguageController extends Controller
{
    public function get()
    {
        $languages = json_decode(BusinessSetting::where(['key' => 'language'])->first()->value, true);

        $languages = array_map(function ($lang) {
            return array(
                'key' => $lang,
                'value'=> \App\CentralLogics\Helpers::get_language_name($lang)
            );
        }, $languages);

        return response()->json($languages, 200);
    }
}
