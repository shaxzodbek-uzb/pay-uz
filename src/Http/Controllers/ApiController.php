<?php
/**
 * Created by PhpStorm.
 * User: Azizbek Eshonaliyev
 * Date: 2/15/2019
 * Time: 5:01 PM
 */

namespace Goodoneuz\PayUz\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    const ERROR_RESPONSE = 'error';
    const SUCCESS_RESPONSE = 'success';

    public function file_put(Request $request)
    {
        if(isset($request['content']) && isset($request['file_name']))
        {
            if (file_exists(base_path('/app/Http/Controllers/Payments/'.$request['file_name'].'.php'))){
                file_put_contents(base_path('/app/Http/Controllers/Payments/'.$request['file_name'].'.php'), $request['content']);
            }else{
                return response()->json([
                    'responseStatus'    => self::ERROR_RESPONSE,
                    'message'           => trans('pay-uz::strings.file_not_found')
                ]);           
            }
        }
        else
        {
            return response()->json([
                'responseStatus'    => self::ERROR_RESPONSE,
                'message'           => trans('pay-uz::strings.request_validate_error')
            ]);       
        }
        return response()->json([
            'responseStatus'    => self::SUCCESS_RESPONSE,
            'message'           => trans('pay-uz::strings.store_success')
        ]);       
    }
}
