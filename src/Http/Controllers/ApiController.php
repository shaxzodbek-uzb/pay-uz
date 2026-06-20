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

    /**
     * The only files the editor is allowed to write. These are the listener /
     * converter hooks published to app/Http/Controllers/Payments and later
     * require()d by PaymentService, so their content is executed — the write
     * surface MUST be strictly bounded.
     */
    const EDITABLE_FILES = [
        'before_pay', 'after_pay', 'paying', 'cancel_pay', 'before_response',
        'key_model', 'model_key', 'is_proper',
    ];

    public function file_put(Request $request)
    {
        // The 'file_name' must be one of the known hook files. An allow-list (not a
        // sanitiser) is used on purpose: it makes path traversal impossible — no '/',
        // '..' or absolute path can ever match — and it documents the write surface.
        if (! isset($request['content'], $request['file_name'])
            || ! in_array($request['file_name'], self::EDITABLE_FILES, true)) {
            return response()->json([
                'responseStatus' => self::ERROR_RESPONSE,
                'message'        => trans('pay-uz::strings.request_validate_error'),
            ]);
        }

        $baseDir = realpath(base_path('app/Http/Controllers/Payments'));
        $target  = base_path('app/Http/Controllers/Payments/'.$request['file_name'].'.php');
        $real    = realpath($target); // the file must already exist (published hook)

        // Defence in depth: the resolved path must live directly inside the
        // Payments directory. Guards against symlink tricks even if the allow-list
        // were ever relaxed.
        if ($baseDir === false || $real === false || dirname($real) !== $baseDir) {
            return response()->json([
                'responseStatus' => self::ERROR_RESPONSE,
                'message'        => trans('pay-uz::strings.file_not_found'),
            ]);
        }

        file_put_contents($real, $request['content']);

        return response()->json([
            'responseStatus' => self::SUCCESS_RESPONSE,
            'message'        => trans('pay-uz::strings.store_success'),
        ]);
    }
}
